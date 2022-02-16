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
use MongoDB\Driver\WriteConcern;

class ReadPreferenceTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $user = new User();
        $user->addGroup(new Group('Test'));
        $this->dm->persist($user);
        $this->dm->flush(['writeConcern' => new WriteConcern('majority')]);
        $this->dm->clear();
    }

    public function testHintIsNotSetByDefault(): void
    {
        $query = $this->dm->getRepository(User::class)
            ->createQueryBuilder()
            ->getQuery();

        $this->assertArrayNotHasKey('readPreference', $query->getQuery());

        $user = $query->getSingleResult();
        $this->assertInstanceOf(User::class, $user);

        $groups = $user->getGroups();
        $this->assertInstanceOf(PersistentCollectionInterface::class, $groups);
        $this->assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $groups->getHints());
    }

    /**
     * @dataProvider provideReadPreferenceHints
     */
    public function testHintIsSetOnQuery(int $readPreference, array $tags = []): void
    {
        $this->skipTestIfSharded(User::class);

        $query = $this->dm->getRepository(User::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference($readPreference, $tags))
            ->getQuery();

        $this->assertReadPreferenceHint($readPreference, $query->getQuery()['readPreference'], $tags);

        $user = $query->getSingleResult();
        $this->assertInstanceOf(User::class, $user);

        $groups = $user->getGroups();
        $this->assertInstanceOf(PersistentCollectionInterface::class, $groups);
        $this->assertReadPreferenceHint($readPreference, $groups->getHints()[Query::HINT_READ_PREFERENCE], $tags);
    }

    public function provideReadPreferenceHints(): array
    {
        return [
            [ReadPreference::RP_PRIMARY, []],
            [ReadPreference::RP_SECONDARY_PREFERRED, []],
            [ReadPreference::RP_SECONDARY, [['dc' => 'east'], []]],
        ];
    }

    public function testDocumentLevelReadPreferenceIsSetInCollection(): void
    {
        $coll = $this->dm->getDocumentCollection(DocumentWithReadPreference::class);

        $this->assertSame(ReadPreference::RP_NEAREST, $coll->getReadPreference()->getMode());
        $this->assertSame([['dc' => 'east']], $coll->getReadPreference()->getTagSets());
    }

    public function testDocumentLevelReadPreferenceIsAppliedInQueryBuilder(): void
    {
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::RP_NEAREST, $query->getQuery()['readPreference'], [['dc' => 'east']]);
    }

    public function testDocumentLevelReadPreferenceCanBeOverriddenInQueryBuilder(): void
    {
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference('secondary', []))
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::RP_SECONDARY, $query->getQuery()['readPreference']);
    }

    private function assertReadPreferenceHint(int $mode, ReadPreference $readPreference, array $tags = []): void
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
    /**
     * @ODM\Id()
     *
     * @var string|null
     */
    public $id;
}
