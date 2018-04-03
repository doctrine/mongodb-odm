<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Group;
use Documents\User;
use MongoDB\Driver\ReadPreference;

class ReadPreferenceTest extends BaseTest
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
        $query = $this->dm->getRepository(User::class)
            ->createQueryBuilder()
            ->getQuery();

        $this->assertArrayNotHasKey('readPreference', $query->getQuery());

        $user = $query->getSingleResult();

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getGroups());
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $user->getGroups()->getHints());
    }

    /**
     * @group replication_lag
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnQuery($readPreference, array $tags = [])
    {
        $query = $this->dm->getRepository(User::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference($readPreference, $tags))
            ->getQuery();

        $this->assertReadPreferenceHint($readPreference, $query->getQuery()['readPreference'], $tags);

        $user = $query->getSingleResult();

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getGroups());
        $this->assertReadPreferenceHint($readPreference, $user->getGroups()->getHints()[Query::HINT_READ_PREFERENCE], $tags);
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
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::RP_NEAREST, $query->getQuery()['readPreference'], [ ['dc' => 'east'] ]);
    }

    public function testDocumentLevelReadPreferenceCanBeOverriddenInQueryBuilder()
    {
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference('secondary', []))
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::RP_SECONDARY, $query->getQuery()['readPreference']);
    }

    private function assertReadPreferenceHint($mode, $readPreference, array $tags = [])
    {
        $this->assertInstanceOf(ReadPreference::class, $readPreference);
        $this->assertEquals($mode, $readPreference->getMode());
        $this->assertEquals($tags, $readPreference->getTagSets());
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
