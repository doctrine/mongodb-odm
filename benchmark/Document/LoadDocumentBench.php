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
    private const NUMBER_OF_ADDITIONAL_USERS = 25;

    private static ObjectId $userId;

    public function init(): void
    {
        self::$userId = new ObjectId();

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
        $this->getDocumentManager()->clear();
        $this->getDocumentManager()->getRepository(User::class)->findOneBy(['username' => 'alcaeus']);
    }

    public function benchLoadCollectionOfDocuments(): void
    {
        $this->getDocumentManager()->clear();
        $this->getDocumentManager()->getRepository(User::class)->findBy([]);
    }

    public function benchLoadReferenceManyCollectionInitialization(): void
    {
        $this->getDocumentManager()->clear();
        $this->loadDocument()->getGroups()->count();
    }

    private function loadDocument(): User
    {
        $document = $this->getDocumentManager()->find(User::class, self::$userId);
        assert($document instanceof User);

        return $document;
    }
}
