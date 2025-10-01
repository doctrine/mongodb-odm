<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\SchemaException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsArticle;
use Documents\CmsUser;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\Group;

#[Group('atlas')]
class AtlasSearchTest extends BaseTestCase
{
    public function testAtlasSearch(): void
    {
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->createDocumentCollection(CmsArticle::class);

        $user         = new CmsUser();
        $user->status = 'active';
        $this->dm->persist($user);

        $article1        = new CmsArticle();
        $article1->topic = 'Technology';
        $article1->title = 'Introduction to MongoDB Atlas Search';
        $article1->text  = 'MongoDB Atlas Search provides full-text search capabilities with advanced features like autocomplete and fuzzy matching.';
        $article1->setAuthor($user);

        $article2        = new CmsArticle();
        $article2->topic = 'Database';
        $article2->title = 'Working with Document Databases';
        $article2->text  = 'Document databases like MongoDB offer flexible schema design and powerful query capabilities for modern applications.';
        $article2->setAuthor($user);

        $article3        = new CmsArticle();
        $article3->topic = 'Programming';
        $article3->title = 'PHP and MongoDB Integration';
        $article3->text  = 'The MongoDB ODM for PHP provides an easy way to work with MongoDB documents using object-oriented programming.';
        $article3->setAuthor($user);

        $this->dm->persist($article1);
        $this->dm->persist($article2);
        $this->dm->persist($article3);

        // Write with majority concern to ensure data is visible for search
        $this->dm->flush(['writeConcern' => new WriteConcern(WriteConcern::MAJORITY)]);

        // Index must be created after data insertion, so the index status is not immediately "READY"
        $schemaManager->createDocumentSearchIndexes(CmsArticle::class);

        // Wait for the search index to be ready (Atlas Local needs time to build the index)
        $schemaManager->waitForSearchIndexes([CmsArticle::class, CmsUser::class]);

        $results = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->autocomplete()
                    ->query('Mongo')
                    ->path('title')
                    ->fuzzy(2, 2)
            ->limit(5)
            ->getAggregation()->execute()->toArray();

        $this->assertNotEmpty($results, 'Autocomplete search should return results');

        $results = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->compound()
                    ->must()
                        ->text()
                            ->query('database')
                            ->path('text')
                    ->should()
                        ->text()
                            ->query('MongoDB')
                            ->path('title')
            ->addFields()
                ->field('score')
                ->expression(['$meta' => 'searchScore'])
            ->sort(['score' => 'searchScore'])
            ->getAggregation()->execute()->toArray();

        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertStringContainsStringIgnoringCase('database', $result['text']);
        }

        $results = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->text()
                    ->query('Atlas Search')
                    ->path('text')
                ->highlight('text', 100, 1)
            ->addFields()
                ->field('highlights')
                ->expression(['$meta' => 'searchHighlights'])
            ->getAggregation()->execute()->toArray();

        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertIsArray($result['highlights']);
        }

        $results = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->text()
                    ->query('MongoDB')
                    ->path('title', 'text')
                ->countDocuments('total')
            ->getAggregation()->execute()->toArray();

        $this->assertNotEmpty($results, 'Count search should return results');
    }

    public function testIndexNotCreated(): void
    {
        $aggregation = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->text()
                ->query('Atlas Search')
                ->path('text')
            ->getAggregation();

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('#^The search index "search_articles" of the collection "[^."]+\.CmsArticle" is not found\.$#');

        $aggregation->execute();
    }

    public function testIndexNotCreatedWithoutException(): void
    {
        $this->dm->getConfiguration()->setAssertSearchIndexExistsForEmptyResult(false);

        $results = $this->dm->createAggregationBuilder(CmsArticle::class)
            ->search()
                ->index('search_articles')
                ->text()
                ->query('Atlas Search')
                ->path('text')
            ->getAggregation()->execute();

        $this->assertCount(0, $results->toArray());
    }

    public function testIndexNotCreatedWithCustomStage(): void
    {
        $aggregation = ($builder = $this->dm->createAggregationBuilder(CmsArticle::class))
            ->addStage(new class ($builder) extends Stage {
                public function getExpression(): array
                {
                    return ['$search' => ['text' => ['query' => 'Atlas Search', 'path' => 'text']]];
                }
            })->getAggregation();

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('#^The search index "default" of the collection "[^."]+\.CmsArticle" is not found\.$#');

        $aggregation->execute();
    }
}
