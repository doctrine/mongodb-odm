<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Group;
use Documents\User;
use MongoDB\Driver\ReadPreference;

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

        $user = $cursor->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->getGroups());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnQuery($readPreference, array $tags = [])
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference($readPreference, $tags))
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
    public function testHintIsSetOnCursor($readPreference, array $tags = [])
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $cursor->setHints(array(
            Query::HINT_READ_PREFERENCE => new ReadPreference($readPreference, $tags),
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
    public function testHintIsSetOnPersistentCollection($readPreference, array $tags = [])
    {
        $cursor = $this->dm->getRepository('Documents\User')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $cursor->getHints());

        $user = $cursor->getSingleResult();
        $groups = $user->getGroups();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $groups);

        $groups->setHints(array(
            Query::HINT_READ_PREFERENCE => new ReadPreference($readPreference, $tags),
        ));

        $this->assertReadPreferenceHint($readPreference, $user->getGroups()->getHints());
        $this->assertReadPreferenceTagsHint($tags, $user->getGroups()->getHints());
    }

    public function provideReadPreferenceHints()
    {
        return [
            [ReadPreference::RP_PRIMARY, []],
            [ReadPreference::RP_SECONDARY_PREFERRED, []],
            [ReadPreference::RP_SECONDARY, [['dc' => 'east'], []]],
        ];
    }

    public function testDocumentLevelReadPreferenceIsSetInCollection()
    {
        $coll = $this->dm->getDocumentCollection(DocumentWithReadPreference::class);

        $this->assertSame(ReadPreference::RP_NEAREST, $coll->getReadPreference()->getMode());
        $this->assertSame([['dc' => 'east']], $coll->getReadPreference()->getTagSets());
    }

    public function testDocumentLevelReadPreferenceIsAppliedInQueryBuilder()
    {
        $cursor = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertReadPreferenceHint(ReadPreference::RP_NEAREST, $cursor->getHints());
        $this->assertReadPreferenceTagsHint([ ['dc' => 'east'] ], $cursor->getHints());
    }

    public function testDocumentLevelReadPreferenceCanBeOverriddenInQueryBuilder()
    {
        $cursor = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference("secondary", []))
            ->getQuery()
            ->execute();

        $this->assertReadPreferenceHint(ReadPreference::RP_SECONDARY, $cursor->getHints());
        $this->assertReadPreferenceTagsHint([], $cursor->getHints());
    }

    private function assertReadPreferenceHint($readPreference, $hints)
    {
        $this->assertInstanceOf(ReadPreference::class, $hints[Query::HINT_READ_PREFERENCE]);
        $this->assertEquals($readPreference, $hints[Query::HINT_READ_PREFERENCE]->getMode());
    }

    private function assertReadPreferenceTagsHint(array $tags = [], $hints)
    {
        $this->assertInstanceOf(ReadPreference::class, $hints[Query::HINT_READ_PREFERENCE]);
        $this->assertEquals($tags, $hints[Query::HINT_READ_PREFERENCE]->getTagSets());
    }
}

/**
 * @ODM\Document()
 * @ODM\ReadPreference("nearest", tags={ { "dc"="east" } })
 */
class DocumentWithReadPreference
{
    /** @ODM\Id() */
    public $id;
}
