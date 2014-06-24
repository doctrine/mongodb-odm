<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Phonenumber;
use Documents\Group;
use Documents\User;

class AssociationCascadePersistTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testManyReferenceAddAndPersist()
    {
        $user = new \Documents\User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $groups = $user2->getGroups();

        $user2->addGroup(new Group('Group'));
        $this->assertEquals(1, count($groups));
        $this->dm->persist($user2);
        $this->dm->flush();
        $groups->initialize();
        $this->assertEquals(1, count($groups));
    }

    public function testManyEmbeddedAddAndPersist()
    {
        $user = new \Documents\User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $phoneNumbers = $user2->getPhoneNumbers();

        $user2->addPhoneNumber(new Phonenumber('555 555 555'));
        $this->assertEquals(1, count($phoneNumbers));
        $this->dm->persist($user2);
        $this->dm->flush();
        $phoneNumbers->initialize();
        $this->assertEquals(1, count($phoneNumbers));
    }
}
