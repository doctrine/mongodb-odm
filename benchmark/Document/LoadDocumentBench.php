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

use function assert;

/**
 * Every subject here reads a document that was persisted in init(). Once a
 * managed document is already fully initialized, UnitOfWork::getOrCreateDocument()
 * skips hydration entirely unless a refresh is requested, so a second
 * revolution against the same document would measure "driver round trip
 * plus skipped hydration" rather than a real load. Revs is therefore kept
 * at 1: since each iteration runs in its own process (see
 * Runner::runIteration()), that's enough to guarantee a cold identity map
 * without needing clear() (which would itself pollute the timed block) or
 * a pool of documents to rotate through. Warmup defaults to 0 (no Warmup
 * attribute at any level in this class), which matters for the same
 * reason: a warmup call runs the same subject in the same process, which
 * would warm the identity map before the timed revolution ever runs.
 * Iterations is raised to make up for the lost statistical power of a
 * single rev.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Revs(1)]
#[Iterations(5)]
final class LoadDocumentBench extends BaseBench
{
    private const NUMBER_OF_ADDITIONAL_USERS = 25;

    private static ObjectId $userId;

    private static ObjectId $referenceManyUserId;

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

        self::$referenceManyUserId = new ObjectId();

        $referenceManyUser = new User();
        $referenceManyUser->setId(self::$referenceManyUserId);
        $referenceManyUser->setUsername('groupUser');
        $referenceManyUser->setCreatedAt(new DateTimeImmutable());
        $referenceManyUser->addGroup($group1);
        $referenceManyUser->addGroup($group2);

        $this->getDocumentManager()->persist($referenceManyUser);

        $this->getDocumentManager()->flush();

        // Prime hydrator classes (one generated class per document type,
        // including embedded ones - see DocumentsUserHydrator.php) and
        // lazy-reference proxy classes once, untimed, so the timed
        // revolutions below don't pay a one-off class-generation cost that
        // a warm application would never see. Embedded fields (address,
        // phonenumbers) are hydrated eagerly by find(), but references
        // (account, groups) are lazy and only hydrated on access. The
        // clear() first is essential: persist()+flush() leaves $user
        // managed, so without it find() would be satisfied by the identity
        // map and never actually hydrate anything.
        $this->getDocumentManager()->clear();
        $primingUser = $this->getDocumentManager()->find(User::class, self::$userId);
        assert($primingUser instanceof User);
        $primingUser->getAccount()->getName();
        $primingUser->getGroups()->forAll(static function (int $key, Group $group) {
            return $group->getName() !== null;
        });

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
        // Cold load, then load again without an intervening clear() so the
        // second call hits UnitOfWork::tryGetById(). Both calls belong in
        // the same revolution on purpose: it's the cold/warm pair itself
        // that's being measured.
        $this->loadDocument();
        $this->loadDocument();
    }

    public function benchLoadDocumentByQuery(): void
    {
        $this->getDocumentManager()->getRepository(User::class)->findOneBy(['username' => 'alcaeus']);
    }

    public function benchLoadCollectionOfDocuments(): void
    {
        $this->getDocumentManager()->getRepository(User::class)->findBy([]);
    }

    public function benchLoadReferenceManyCollectionInitialization(): void
    {
        $document = $this->getDocumentManager()->find(User::class, self::$referenceManyUserId);
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
