<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\SchemaException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsAddress;
use Documents\CmsArticle;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

use function bin2hex;
use function hrtime;
use function random_bytes;

#[Group('atlas')]
class SchemaManagerWaitForSearchIndexesTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Randomize the collection name to avoid collisions when search indexes
        // are created or dropped asynchronously
        $this->dm->getClassMetadata(CmsArticle::class)
            ->setCollection('articles_' . bin2hex(random_bytes(4)));
    }

    #[TestWith([0])]
    #[TestWith([50_000])]
    public function testWait(int $nbDocuments): void
    {
        $schemaManager = $this->dm->getSchemaManager();
        $collection    = $this->dm->getDocumentCollection(CmsArticle::class);

        $schemaManager->createDocumentCollection(CmsArticle::class);

        if ($nbDocuments) {
            $bulk = new BulkWrite();
            for ($i = 0; $i < $nbDocuments; $i++) {
                $bulk->insert(['topic' => 'topic ' . $i, 'title' => 'title ' . $i, 'text' => 'text ' . $i]);
            }

            $collection->getManager()->executeBulkWrite(
                $collection->getNamespace(),
                $bulk,
                ['writeConcern' => new WriteConcern(WriteConcern::MAJORITY)],
            );
        }

        // The index must be created after data insertion, so the index status is not immediately "READY"
        $schemaManager->createDocumentSearchIndexes(CmsArticle::class);

        $this->assertNotSame('READY', $collection->listSearchIndexes(['name' => 'search_articles'])->current()['status']);

        $start = hrtime(true);
        $schemaManager->waitForSearchIndexes([CmsArticle::class]);
        $timeMs = (hrtime(true) - $start) / 1_000_000;

        $this->assertSame($nbDocuments, $collection->aggregate([
            [
                '$searchMeta' => [
                    'index' => 'search_articles',
                    'exists' => ['path' => '_id'],
                    'count' => ['type' => 'total'],
                ],
            ],
        ])->toArray()[0]['count']['total'], 'All documents are indexed');

        $this->assertSame('READY', $collection->listSearchIndexes(['name' => 'search_articles'])->current()['status'], 'Ready after ' . $timeMs . ' ms');
    }

    public function testErrors(): void
    {
        $schemaManager = $this->dm->getSchemaManager();

        // Search index missing
        try {
            $schemaManager->waitForSearchIndexes([CmsArticle::class]);
            $this->fail('Expected SchemaException not thrown');
        } catch (SchemaException $exception) {
            $this->assertSame('The document class "Documents\CmsArticle" is missing the following search index(es): "search_articles"', $exception->getMessage());
        }

        $schemaManager->createDocumentCollection(CmsArticle::class);
        $schemaManager->createDocumentSearchIndexes(CmsArticle::class);

        // Timeout too short
        try {
            $schemaManager->waitForSearchIndexes([CmsArticle::class], 1);
            $this->fail('Expected SchemaException not thrown');
        } catch (MongoDBException $exception) {
            $this->assertSame('Timed out waiting for search indexes to become queryable after 1 ms. Search indexes are not ready for the following class(es): Documents\CmsArticle', $exception->getMessage());
        }

        // Not specifying classes waits for all
        try {
            $schemaManager->waitForSearchIndexes([CmsArticle::class, CmsAddress::class]);
            $this->fail('Expected SchemaException not thrown');
        } catch (SchemaException $exception) {
            // The missing class varies depending on the test execution order,
            // classes are added to the ClassMetadataFactory in the order they are used
            $this->assertMatchesRegularExpression('#The document class "Documents\\\\(CmsAddress|VectorEmbedding)" is missing the following search index\(es\): "default"#', $exception->getMessage());
        }

        // Remove the collection
        $schemaManager->dropDocumentCollection(CmsArticle::class);

        try {
            $schemaManager->waitForSearchIndexes([CmsArticle::class]);
            $this->fail('Expected SchemaException not thrown');
        } catch (SchemaException $exception) {
            $this->assertSame('The document class "Documents\CmsArticle" is missing the following search index(es): "search_articles"', $exception->getMessage());
        }
    }
}
