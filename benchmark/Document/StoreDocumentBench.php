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
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

final class StoreDocumentBench extends BaseBench
{
    /** @Warmup(2) */
    public function benchStoreDocument(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    /** @Warmup(2) */
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

    /** @Warmup(2) */
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

    /** @Warmup(2) */
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

    /** @Warmup(2) */
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
}
