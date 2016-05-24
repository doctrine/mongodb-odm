<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Group;
use Documents\User;

class ReadPreferenceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

    public function testHintIsNotSetByDefault()
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $cursor->getHints());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE_TAGS, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $user->getGroups()->getHints());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE_TAGS, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnQuery($readPreference, array $tags = null)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->setReadPreference($readPreference, $tags)
            ->getQuery()
            ->execute();

        $this->assertReadPreferenceHint($readPreference, $cursor->getHints());
        $this->assertReadPreferenceTagsHint($tags, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertReadPreferenceHint($readPreference, $user->getGroups()->getHints());
        $this->assertReadPreferenceTagsHint($tags, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnCursor($readPreference, array $tags = null)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $cursor->setHints(array(
            Query::HINT_READ_PREFERENCE => $readPreference,
            Query::HINT_READ_PREFERENCE_TAGS => $tags,
        ));

        $this->assertReadPreferenceHint($readPreference, $cursor->getHints());
        $this->assertReadPreferenceTagsHint($tags, $cursor->getHints());

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertReadPreferenceHint($readPreference, $user->getGroups()->getHints());
        $this->assertReadPreferenceTagsHint($tags, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnPersistentCollection($readPreference, array $tags = null)
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $cursor->getHints());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE_TAGS, $cursor->getHints());

        $user = $cursor->getSingleResult();
        $groups = $user->getGroups();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $groups);

        $groups->setHints(array(
            Query::HINT_READ_PREFERENCE => $readPreference,
            Query::HINT_READ_PREFERENCE_TAGS => $tags,
        ));

        $this->assertReadPreferenceHint($readPreference, $user->getGroups()->getHints());
        $this->assertReadPreferenceTagsHint($tags, $user->getGroups()->getHints());
    }

    public function provideReadPreferenceHints()
    {
        return array(
            array('primary', null),
            array('secondaryPreferred', array()),
            array('secondary', array(array('dc' => 'east'), array())),
        );
    }

    private function assertReadPreferenceHint($readPreference, $hints)
    {
        $this->assertEquals($readPreference, $hints[Query::HINT_READ_PREFERENCE]);
    }

    private function assertReadPreferenceTagsHint(array $tags = null, $hints)
    {
        $this->assertEquals($tags, $hints[Query::HINT_READ_PREFERENCE_TAGS]);
    }
}
