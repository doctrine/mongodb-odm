<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Documents\AutoEmbeddingArticle;
use Documents\VectorEmbedding;
use MongoDB\BSON\Binary;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function str_contains;

#[Group('atlas')]
class VectorSearchTest extends BaseTestCase
{
    public function testAtlasVectorSearch(): void
    {
        // Create the collection by ensuring the schema
        $schemaManager = $this->dm->getSchemaManager();

        // Create the collection and vector search indexes
        $schemaManager->createDocumentCollection(VectorEmbedding::class);

        // Insert some test documents with vector embeddings
        $doc1              = new VectorEmbedding();
        $doc1->vectorFloat = [1.0, 2.0, 3.0];
        $doc1->vectorInt   = [1, 2, 3];
        $doc1->filterField = 'active';

        $doc2              = new VectorEmbedding();
        $doc2->vectorFloat = [4.0, 5.0, 6.0];
        $doc2->vectorInt   = [4, 5, 6];
        $doc2->filterField = 'inactive';

        $doc3              = new VectorEmbedding();
        $doc3->vectorFloat = [1.5, 2.5, 3.5];
        $doc3->vectorInt   = [2, 3, 4];
        $doc3->filterField = 'active';

        $this->dm->persist($doc1);
        $this->dm->persist($doc2);
        $this->dm->persist($doc3);
        // Write with majority concern to ensure data is visible for search
        $this->dm->flush(['writeConcern' => new WriteConcern(WriteConcern::MAJORITY)]);

        // Index must be created after data insertion, so the index status is not immediately "READY"
        $schemaManager->createDocumentSearchIndexes(VectorEmbedding::class);

        // Wait for the search index to be ready (Atlas Local needs time to build the index)
        $schemaManager->waitForSearchIndexes([VectorEmbedding::class]);

        $results = $this->dm->createQueryBuilder(VectorEmbedding::class)->getQuery()->toArray();
        $this->assertCount(3, $results, 'All documents should be present in the collection');

        foreach ($results as $result) {
            $this->assertInstanceOf(VectorEmbedding::class, $result);

            $this->assertIsArray($result->vectorFloat);
            $this->assertCount(3, $result->vectorFloat);
            $this->assertIsArray($result->vectorInt);
            $this->assertCount(3, $result->vectorInt);
        }

        $results = $this->dm->createAggregationBuilder(VectorEmbedding::class)
            ->vectorSearch()
                ->index('default')
                ->queryVector([1.1, 2.1, 3.1])
                ->path('vectorFloat')
                ->numCandidates(10)
                ->limit(5)
            ->set()
                ->field('score')
                ->expression(['$meta' => 'vectorSearchScore'])
            ->getAggregation()->execute()->toArray();

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertIsFloat($result['score'], 'Result should have a score');
        }

        // Test with filter
        $results = ($builder = $this->dm->createAggregationBuilder(VectorEmbedding::class))
            ->hydrate(VectorEmbedding::class)
            ->vectorSearch()
                ->index('vector_int')
                ->queryVector([1, 1, 3])
                ->path('vectorInt')
                ->numCandidates(10)
                ->limit(5)
                ->filter($builder->matchExpr()->field('filterField')->equals('active'))
            ->getAggregation()->execute()->toArray();

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(VectorEmbedding::class, $result);
            $this->assertEquals('active', $result->filterField, 'Filtered results should only contain active documents');
        }
    }

    public function testAtlasAutoEmbedding(): void
    {
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->createDocumentCollection(AutoEmbeddingArticle::class);

        $doc1           = new AutoEmbeddingArticle();
        $doc1->content  = 'MongoDB is a document-oriented NoSQL database';
        $doc1->category = 'database';

        $doc2           = new AutoEmbeddingArticle();
        $doc2->content  = 'Atlas Vector Search enables semantic similarity queries on MongoDB';
        $doc2->category = 'database';

        // Non-matching documents — unrelated topics
        $doc3           = new AutoEmbeddingArticle();
        $doc3->content  = 'How to make a perfect chocolate cake at home';
        $doc3->category = 'cooking';

        $doc4           = new AutoEmbeddingArticle();
        $doc4->content  = 'Top 10 hiking trails in the Swiss Alps';
        $doc4->category = 'travel';

        $doc5           = new AutoEmbeddingArticle();
        $doc5->content  = 'The history of the Olympic Games from ancient Greece to today';
        $doc5->category = 'sports';

        $this->dm->persist($doc1);
        $this->dm->persist($doc2);
        $this->dm->persist($doc3);
        $this->dm->persist($doc4);
        $this->dm->persist($doc5);
        $this->dm->flush(['writeConcern' => new WriteConcern(WriteConcern::MAJORITY)]);

        try {
            $schemaManager->createDocumentSearchIndexes(AutoEmbeddingArticle::class);
        } catch (CommandException $e) {
            if (str_contains($e->getMessage(), 'not registered')) {
                $this->markTestSkipped('Autoembedding requires an Atlas cluster with a registered embedding model. Set VOYAGE_API_KEY');
            }

            throw $e;
        }

        $schemaManager->waitForSearchIndexes([AutoEmbeddingArticle::class], maxTimeMs: 120_000);

        // Query with a lighter model (voyage-4-lite) than the indexing model (voyage-4-large); all voyage-4 embeddings are compatible
        $results = $this->dm->createAggregationBuilder(AutoEmbeddingArticle::class)
            ->hydrate(AutoEmbeddingArticle::class)
            ->vectorSearch()
                ->index('default')
                ->path('content')
                ->query('NoSQL document database')
                ->model('voyage-4-lite')
                ->numCandidates(10)
                ->limit(2)
            ->getAggregation()->execute()->toArray();

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(AutoEmbeddingArticle::class, $result);
            $this->assertSame('database', $result->category, 'Expected only database-related results');
        }
    }

    #[RequiresPhpExtension('mongodb', '>= 2.2')]
    public function testAtlasVectorSearchWithBinaryType(): void
    {
        $cm = $this->dm->getClassMetadata(VectorEmbedding::class);

        $cm->fieldMappings['vectorFloat']['type'] = Type::VECTOR_FLOAT32;
        $cm->fieldMappings['vectorInt']['type']   = Type::VECTOR_INT8;

        // Change the collection name to avoid conflicts with asynchronous index building
        $cm->collection .= '_binary_type';

        $this->testAtlasVectorSearch();

        // Ensure that the vectors are stored in as binary vectors
        $doc = $this->dm->getDocumentCollection(VectorEmbedding::class)->findOne(['filterField' => 'active']);
        $this->assertIsArray($doc);
        $this->assertInstanceOf(Binary::class, $doc['vectorInt']);
        $this->assertInstanceOf(Binary::class, $doc['db_vector_float']);
    }
}
