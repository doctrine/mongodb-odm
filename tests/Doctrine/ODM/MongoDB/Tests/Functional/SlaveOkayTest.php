<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Group;
use Documents\User;

class SlaveOkayTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->addGroup(new Group('Test'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * @group replication_lag
     */
    public function testHintIsNotSetByDefault()
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertArrayNotHasKey(Query::HINT_SLAVE_OKAY, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertArrayNotHasKey(Query::HINT_SLAVE_OKAY, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideSlaveOkayHints
     */
    public function testHintIsSetOnQuery($slaveOkay)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->slaveOkay($slaveOkay)
            ->getQuery()
            ->execute();

        $this->assertSlaveOkayHint($slaveOkay, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertSlaveOkayHint($slaveOkay, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideSlaveOkayHints
     */
    public function testHintIsSetOnCursor($slaveOkay)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $cursor->setHints(array(Query::HINT_SLAVE_OKAY => $slaveOkay));

        $this->assertSlaveOkayHint($slaveOkay, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertSlaveOkayHint($slaveOkay, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideSlaveOkayHints
     */
    public function testHintIsSetOnPersistentCollection($slaveOkay)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertArrayNotHasKey(Query::HINT_SLAVE_OKAY, $cursor->getHints());

        $user = $cursor->getSingleResult();
        $groups = $user->getGroups();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $groups);

        $groups->setHints(array(Query::HINT_SLAVE_OKAY => $slaveOkay));

        $this->assertSlaveOkayHint($slaveOkay, $groups->getHints());
    }

    public function provideSlaveOkayHints()
    {
        return array(
            array(true),
            array(false)
        );
    }

    public function testSlaveOkayHintFromClassMetadata()
    {
        $users = $this->dm->getRepository(__NAMESPACE__.'\SlaveOkayDocument')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertSlaveOkayHint(true, $users->getHints());
    }

    private function assertSlaveOkayHint($slaveOkay, $hints)
    {
        $this->assertEquals($slaveOkay, $hints[Query::HINT_SLAVE_OKAY]);
    }
}

/** @ODM\Document(slaveOkay=true) */
class SlaveOkayDocument
{
    /** @ODM\Id */
    public $id;
}
