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

#[BeforeMethods(['initDocumentManager', 'clearDatabase'])]
#[Warmup(2)]
#[Revs(100)]
#[Iterations(5)]
final class StoreDocumentBench extends BaseBench
{
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
}
