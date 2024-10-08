<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Group;
use Documents\User;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\DataProvider;

/** @phpstan-type ReadPreferenceTagShape array{dc?: string, usage?: string} */
class ReadPreferenceTest extends BaseTestCase
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

        self::assertArrayNotHasKey('readPreference', $query->getQuery());

        $user = $query->getSingleResult();
        self::assertInstanceOf(User::class, $user);

        $groups = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        self::assertArrayNotHasKey(Query::HINT_READ_PREFERENCE, $groups->getHints());
    }

    /** @phpstan-param ReadPreferenceTagShape[] $tags */
    #[DataProvider('provideReadPreferenceHints')]
    public function testHintIsSetOnQuery(string $readPreference, array $tags = []): void
    {
        $this->skipTestIfSharded(User::class);

        $query = $this->dm->getRepository(User::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference($readPreference, $tags))
            ->getQuery();

        $this->assertReadPreferenceHint($readPreference, $query->getQuery()['readPreference'], $tags);

        $user = $query->getSingleResult();
        self::assertInstanceOf(User::class, $user);

        $groups = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        $this->assertReadPreferenceHint($readPreference, $groups->getHints()[Query::HINT_READ_PREFERENCE], $tags);
    }

    public static function provideReadPreferenceHints(): array
    {
        return [
            [ReadPreference::PRIMARY, []],
            [ReadPreference::SECONDARY_PREFERRED, []],
            [ReadPreference::SECONDARY, [['dc' => 'east'], []]],
        ];
    }

    public function testDocumentLevelReadPreferenceIsSetInCollection(): void
    {
        $coll = $this->dm->getDocumentCollection(DocumentWithReadPreference::class);

        self::assertSame(ReadPreference::NEAREST, $coll->getReadPreference()->getModeString());
        self::assertSame([['dc' => 'east']], $coll->getReadPreference()->getTagSets());
    }

    public function testDocumentLevelReadPreferenceIsAppliedInQueryBuilder(): void
    {
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::NEAREST, $query->getQuery()['readPreference'], [['dc' => 'east']]);
    }

    public function testDocumentLevelReadPreferenceCanBeOverriddenInQueryBuilder(): void
    {
        $query = $this->dm->getRepository(DocumentWithReadPreference::class)
            ->createQueryBuilder()
            ->setReadPreference(new ReadPreference('secondary', []))
            ->getQuery();

        $this->assertReadPreferenceHint(ReadPreference::SECONDARY, $query->getQuery()['readPreference']);
    }

    /** @phpstan-param ReadPreferenceTagShape[] $tags */
    private function assertReadPreferenceHint(string $mode, ReadPreference $readPreference, array $tags = []): void
    {
        self::assertInstanceOf(ReadPreference::class, $readPreference);
        self::assertEquals($mode, $readPreference->getModeString());
        self::assertEquals($tags, $readPreference->getTagSets());
    }
}

#[ODM\Document]
#[ODM\ReadPreference('nearest', tags: [['dc' => 'east']])]
class DocumentWithReadPreference
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}
