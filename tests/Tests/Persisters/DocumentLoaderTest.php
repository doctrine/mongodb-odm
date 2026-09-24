<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Persisters\DocumentLoader;
use Doctrine\ODM\MongoDB\Persisters\ShardKeyQueryBuilder;
use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

use function array_map;
use function array_values;

/**
 * Unit-style coverage for DocumentLoader, constructed directly rather than
 * through DocumentPersister/UnitOfWork. This complements (rather than
 * replaces) the existing black-box coverage in
 * Functional\DocumentPersisterTest, which exercises document loading
 * indirectly via the public DocumentPersister facade.
 */
class DocumentLoaderTest extends BaseTestCase
{
    /** @var class-string<DocumentLoaderTestDocument> */
    private string $class;

    public function setUp(): void
    {
        parent::setUp();

        $this->class = DocumentLoaderTestDocument::class;

        $collection = $this->dm->getDocumentCollection($this->class);
        $collection->drop();

        foreach (['a', 'b', 'c', 'd'] as $name) {
            $collection->insertOne(['dbName' => $name]);
        }
    }

    /** @phpstan-param class-string $className */
    private function createLoader(string $className): DocumentLoader
    {
        $class                = $this->dm->getClassMetadata($className);
        $cm                   = new CriteriaMerger();
        $criteriaPreparer     = new CriteriaPreparer($this->dm, $this->uow->getPersistenceBuilder(), $class, $cm);
        $shardKeyQueryBuilder = new ShardKeyQueryBuilder($this->dm, $this->uow, $class, $criteriaPreparer);

        return new DocumentLoader(
            $this->dm,
            $this->uow,
            $this->dm->getHydratorFactory(),
            $class,
            $criteriaPreparer,
            $shardKeyQueryBuilder,
            $this->dm->getDocumentCollection($className),
        );
    }

    public function testLoadPreparesCriteriaAndSort(): void
    {
        $criteria = ['name' => ['$in' => ['a', 'b']]];
        $sort     = ['name' => -1];

        $document = $this->createLoader($this->class)->load($criteria, null, [], 0, $sort);

        self::assertInstanceOf($this->class, $document);
        self::assertSame('b', $document->name);
    }

    public function testLoadReturnsNullWhenNothingMatches(): void
    {
        $document = $this->createLoader($this->class)->load(['name' => 'nonexistent']);

        self::assertNull($document);
    }

    public function testLoadAllPreparesCriteriaSortLimitAndSkip(): void
    {
        $criteria = ['name' => ['$in' => ['a', 'b', 'c', 'd']]];
        $sort     = ['name' => 1];

        $documents = $this->createLoader($this->class)->loadAll($criteria, $sort, 2, 1)->toArray();

        $names = array_map(static fn (DocumentLoaderTestDocument $d) => $d->name, array_values($documents));
        self::assertSame(['b', 'c'], $names);
    }

    public function testExistsReturnsTrueForExistentDocuments(): void
    {
        $loader   = $this->createLoader($this->class);
        $document = $loader->load(['name' => 'a']);

        self::assertTrue($loader->exists($document));
    }

    public function testExistsReturnsFalseForNonexistentDocuments(): void
    {
        $document     = new DocumentLoaderTestDocument();
        $document->id = new ObjectId();

        self::assertFalse($this->createLoader($this->class)->exists($document));
    }

    public function testLockSetsLockFieldOnDocumentAndInDatabase(): void
    {
        $document       = new DocumentLoaderTestDocument();
        $document->name = 'lockable';

        $this->dm->persist($document);
        $this->dm->flush();

        $this->createLoader($this->class)->lock($document, 2);

        self::assertSame(2, $document->locked);

        $stored = $this->dm->getDocumentCollection($this->class)->findOne(['_id' => new ObjectId($document->id)]);
        self::assertSame(2, $stored['locked']);
    }

    public function testUnlockClearsLockFieldOnDocumentAndInDatabase(): void
    {
        $document       = new DocumentLoaderTestDocument();
        $document->name = 'lockable';

        $this->dm->persist($document);
        $this->dm->flush();

        $loader = $this->createLoader($this->class);
        $loader->lock($document, 2);
        $loader->unlock($document);

        self::assertNull($document->locked);

        $stored = $this->dm->getDocumentCollection($this->class)->findOne(['_id' => new ObjectId($document->id)]);
        self::assertArrayNotHasKey('locked', (array) $stored);
    }

    public function testRefreshUpdatesDocumentDataFromDatabase(): void
    {
        $document       = new DocumentLoaderTestDocument();
        $document->name = 'original';

        $this->dm->persist($document);
        $this->dm->flush();

        $this->dm->getDocumentCollection($this->class)->updateOne(
            ['_id' => new ObjectId($document->id)],
            ['$set' => ['dbName' => 'changed-externally']],
        );

        $this->createLoader($this->class)->refresh($document);

        self::assertSame('changed-externally', $document->name);
    }

    public function testRefreshThrowsWhenDocumentNoLongerExists(): void
    {
        $document       = new DocumentLoaderTestDocument();
        $document->name = 'original';

        $this->dm->persist($document);
        $this->dm->flush();

        $this->dm->getDocumentCollection($this->class)->deleteOne(['_id' => new ObjectId($document->id)]);

        $this->expectException(MongoDBException::class);
        $this->createLoader($this->class)->refresh($document);
    }
}

#[ODM\Document]
class DocumentLoaderTestDocument
{
    /** @var ObjectId|string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(name: 'dbName', type: 'string')]
    public $name;

    /** @var int|null */
    #[ODM\Lock]
    #[ODM\Field(type: 'int')]
    public $locked;
}
