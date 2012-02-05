<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\User;
use Documents\Group;

class SlaveOkayTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSlaveOkayOnPersistentCollection()
    {
        $user = new User();
        $user->addGroup(new Group('Test'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->slaveOkay(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals(array(), $user->getGroups()->getHints());

        $this->dm->clear();

        $users = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertEquals(array(), $users->getHints());

        $users = array_values($users->toArray());
        $user = $users[0];
        $this->assertEquals(array(), $user->getGroups()->getHints());

        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->slaveOkay(true)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $user->getGroups()->getHints());

        $this->dm->clear();

        $users = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute()
            ->slaveOkay(true);

        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $users->getHints());

        $users = array_values($users->toArray());
        $user = $users[0];
        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $user->getGroups()->getHints());

        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->getSingleResult();

        $groups = $user->getGroups();
        $groups->setHints(array(Query::HINT_SLAVE_OKAY => true));
        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $groups->getHints());
    }

    public function testSlaveOkayDocument()
    {
        $users = $this->dm->getRepository(__NAMESPACE__.'\SlaveOkayDocument')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $users->getHints());
    }
}

/** @ODM\Document(slaveOkay=true) */
class SlaveOkayDocument
{
    /** @ODM\Id */
    public $id;
}