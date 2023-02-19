<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Phonenumber;
use Documents\User;

use function get_class;

class GH1132Test extends BaseTestCase
{
    public function testClonedPersistentCollectionCanBeClearedAndUsedInNewDocument(): void
    {
        $u = new User();
        $u->getPhonenumbers()->add(new Phonenumber('123456'));
        $this->dm->persist($u);
        $this->dm->flush();

        $u2 = new User();
        $u2->setPhonenumbers(clone $u->getPhonenumbers());
        $u2->getPhonenumbers()->clear();
        $this->dm->persist($u2);
        $this->dm->flush();
        $this->dm->clear();

        $u = $this->dm->find(get_class($u), $u->getId());
        self::assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        self::assertEmpty($u2->getPhonenumbers());
    }

    public function testClonedPersistentCollectionCanBeClearedAndUsedInManagedDocument(): void
    {
        $u = new User();
        $u->getPhonenumbers()->add(new Phonenumber('123456'));
        $u2 = new User();
        $this->dm->persist($u);
        $this->dm->persist($u2);
        $this->dm->flush();

        $u2->setPhonenumbers(clone $u->getPhonenumbers());
        $u2->getPhonenumbers()->clear();

        $this->dm->flush();
        $this->dm->clear();

        $u = $this->dm->find(get_class($u), $u->getId());
        self::assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        self::assertEmpty($u2->getPhonenumbers());
    }

    public function testClonedPersistentCollectionUpdatesCorrectly(): void
    {
        $u = new User();
        $u->getPhonenumbers()->add(new Phonenumber('123456'));
        $u2 = new User();
        $u2->getPhonenumbers()->add(new Phonenumber('9876543'));
        $u2->getPhonenumbers()->add(new Phonenumber('7654321'));
        $this->dm->persist($u);
        $this->dm->persist($u2);
        $this->dm->flush();

        $u2->setPhonenumbers(clone $u->getPhonenumbers());

        $this->dm->flush();
        $this->dm->clear();

        $u = $this->dm->find(get_class($u), $u->getId());
        self::assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        self::assertCount(1, $u2->getPhonenumbers());
        self::assertSame('123456', $u2->getPhonenumbers()->first()->getPhonenumber());
    }
}
