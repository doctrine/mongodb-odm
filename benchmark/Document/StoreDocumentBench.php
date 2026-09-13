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
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function random_int;

#[BeforeMethods(['initDocumentManager', 'clearDatabase'])]
#[Warmup(2)]
#[Revs(100)]
#[Iterations(5)]
final class StoreDocumentBench extends BaseBench
{
    private static User $updateUser;

    public function benchStoreDocument(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithEmbedOne(): void
    {
        $address = new Address();
        $address->setAddress('Redacted');
        $address->setCity('Munich');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setAddress($address);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithEmbedMany(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->addPhonenumber(new Phonenumber('12345678'));
        $user->addPhonenumber(new Phonenumber('12345678'));

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithReferenceOne(): void
    {
        $account = new Account();
        $account->setName('alcaeus');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setAccount($account);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithReferenceMany(): void
    {
        $group1 = new Group('One');
        $group2 = new Group('Two');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function initUpdateBench(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->addPhonenumber(new Phonenumber('12345678'));

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        self::$updateUser = $user;
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    public function benchUpdateDocument(): void
    {
        self::$updateUser->setHits(self::$updateUser->getHits() + 1);

        $this->getDocumentManager()->flush();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    public function benchUpdateDocumentWithEmbedMany(): void
    {
        self::$updateUser->getPhonenumbers()->first()->setPhoneNumber((string) random_int(10_000_000, 99_999_999));

        $this->getDocumentManager()->flush();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    public function benchComputeChangeSetsOnly(): void
    {
        self::$updateUser->setHits(self::$updateUser->getHits() + 1);

        $this->getDocumentManager()->getUnitOfWork()->computeChangeSets();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase'])]
    #[Revs(20)]
    public function benchStoreManyDocuments(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = new User();
            $user->setUsername('alcaeus' . $i);
            $user->setCreatedAt(new DateTimeImmutable());

            $this->getDocumentManager()->persist($user);
        }

        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchRemoveDocument(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        $this->getDocumentManager()->remove($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }
}
