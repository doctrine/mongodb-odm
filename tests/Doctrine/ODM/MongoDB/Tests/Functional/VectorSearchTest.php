<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\VectorEmbedding;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\Group;

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

        // Wait for search index to be ready (Atlas Local needs time to build the index)
        $schemaManager->waitForSearchIndexes([VectorEmbedding::class]);

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
            $this->assertIsArray($result);
            $this->assertEquals('active', $result['filterField'], 'Filtered results should only contain active documents');
        }
    }
}
