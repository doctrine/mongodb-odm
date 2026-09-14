<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;
use MongoDB\BSON\ObjectId;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function assert;

#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(100)]
#[Iterations(5)]
final class LoadDocumentBench extends BaseBench
{
    private const NUMBER_OF_ADDITIONAL_USERS     = 25;
    private const NUMBER_OF_REFERENCE_MANY_USERS = 100;

    private static ObjectId $userId;

    /** @var list<ObjectId> */
    private static array $referenceManyUserIds = [];

    private static int $referenceManyUserIndex = 0;

    public function init(): void
    {
        self::$userId                 = new ObjectId();
        self::$referenceManyUserIds   = [];
        self::$referenceManyUserIndex = 0;

        $account = new Account();
        $account->setName('alcaeus');

        $address = new Address();
        $address->setAddress('Redacted');
        $address->setCity('Munich');

        $group1 = new Group('One');
        $group2 = new Group('Two');

        $user = new User();
        $user->setId(self::$userId);
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setAddress($address);
        $user->setAccount($account);
        $user->addPhonenumber(new Phonenumber('12345678'));
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->getDocumentManager()->persist($user);

        for ($i = 0; $i < self::NUMBER_OF_ADDITIONAL_USERS; $i++) {
            $additionalUser = new User();
            $additionalUser->setUsername('user' . $i);
            $additionalUser->setCreatedAt(new DateTimeImmutable());

            $this->getDocumentManager()->persist($additionalUser);
        }

        // A distinct pool of users to load from, one per revolution, so that
        // benchLoadReferenceManyCollectionInitialization always measures a
        // cold lazy-collection initialization rather than an identity map hit.
        for ($i = 0; $i < self::NUMBER_OF_REFERENCE_MANY_USERS; $i++) {
            $referenceManyUserId = new ObjectId();

            $referenceManyUser = new User();
            $referenceManyUser->setId($referenceManyUserId);
            $referenceManyUser->setUsername('groupUser' . $i);
            $referenceManyUser->setCreatedAt(new DateTimeImmutable());
            $referenceManyUser->addGroup($group1);
            $referenceManyUser->addGroup($group2);

            $this->getDocumentManager()->persist($referenceManyUser);

            self::$referenceManyUserIds[] = $referenceManyUserId;
        }

        $this->getDocumentManager()->flush();

        $this->getDocumentManager()->clear();
    }

    public function benchLoadDocument(): void
    {
        $this->loadDocument();
    }

    public function benchLoadEmbedOne(): void
    {
        $this->loadDocument()->getAddress()->getCity();
    }

    public function benchLoadEmbedMany(): void
    {
        $this->loadDocument()->getPhonenumbers()->forAll(static function (int $key, Phonenumber $element) {
            return $element->getPhoneNumber() !== null;
        });
    }

    public function benchLoadReferenceOne(): void
    {
        $this->loadDocument()->getAccount()->getName();
    }

    public function benchLoadReferenceMany(): void
    {
        $this->loadDocument()->getGroups()->forAll(static function (int $key, Group $group) {
            return $group->getName() !== null;
        });
    }

    public function benchLoadDocumentFromIdentityMap(): void
    {
        // Warm the identity map, then load again without an intervening
        // clear() so the second call hits UnitOfWork::tryGetById().
        $this->loadDocument();
        $this->loadDocument();
    }

    public function benchLoadDocumentByQuery(): void
    {
        // findOneBy() always queries MongoDB, regardless of identity map
        // state, so no clear() is needed to keep this measurement honest.
        $this->getDocumentManager()->getRepository(User::class)->findOneBy(['username' => 'alcaeus']);
    }

    public function benchLoadCollectionOfDocuments(): void
    {
        // findBy() always queries MongoDB, regardless of identity map state,
        // so no clear() is needed to keep this measurement honest.
        $this->getDocumentManager()->getRepository(User::class)->findBy([]);
    }

    public function benchLoadReferenceManyCollectionInitialization(): void
    {
        $id = self::$referenceManyUserIds[self::$referenceManyUserIndex % self::NUMBER_OF_REFERENCE_MANY_USERS];
        self::$referenceManyUserIndex++;

        $document = $this->getDocumentManager()->find(User::class, $id);
        assert($document instanceof User);

        $document->getGroups()->count();
    }

    private function loadDocument(): User
    {
        $document = $this->getDocumentManager()->find(User::class, self::$userId);
        assert($document instanceof User);

        return $document;
    }
}
