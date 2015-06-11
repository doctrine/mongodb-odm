<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Documents\Phonenumber;
use Documents\User;

class GH1132Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testClonedPersistentCollectionCanBeClearedAndUsedInNewDocument()
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
        $this->assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        $this->assertCount(0, $u2->getPhonenumbers());
    }

    public function testClonedPersistentCollectionCanBeClearedAndUsedInManagedDocument()
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
        $this->assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        $this->assertCount(0, $u2->getPhonenumbers());
    }

    public function testClonedPersistentCollectionUpdatesCorrectly()
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
        $this->assertCount(1, $u->getPhonenumbers());

        $u2 = $this->dm->find(get_class($u2), $u2->getId());
        $this->assertCount(1, $u2->getPhonenumbers());
        $this->assertSame('123456', $u2->getPhonenumbers()->first()->getPhonenumber());
    }
}
