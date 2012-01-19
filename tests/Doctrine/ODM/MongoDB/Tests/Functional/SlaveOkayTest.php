<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

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

        $user = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->slaveOkay(true)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals(array(Query::HINT_SLAVE_OKAY => true), $user->getGroups()->getHints());
    }
}