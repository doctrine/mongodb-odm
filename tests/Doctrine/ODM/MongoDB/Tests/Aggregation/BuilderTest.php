<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Aggregation\Aggregation;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Article;
use Documents\BlogPost;
use Documents\BlogTagAggregation;
use Documents\CmsComment;
use Documents\GuestServer;
use Documents\Tag;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use function array_keys;

class BuilderTest extends BaseTest
{
    public function testGetPipeline(): void
    {
        $point = ['type' => 'Point', 'coordinates' => [0, 0]];

        $expectedPipeline = [
            [
                '$geoNear' => [
                    'near' => $point,
                    'spherical' => true,
                    'distanceField' => 'distance',
                    'query' => [
                        'hasCoordinates' => ['$exists' => true],
                        'username' => 'foo',
                    ],
                    'num' => 10,
                ],
            ],
            [
                '$match' =>
                [
                    '$or' => [
                        ['username' => 'admin'],
                        ['username' => 'administrator'],
                    ],
                    'group' => ['$in' => ['a', 'b']],
                ],
            ],
            ['$sample' => ['size' => 10]],
            [
                '$lookup' =>
                [
                    'from' => 'orders',
                    'localField' => '_id',
                    'foreignField' => 'user.$id',
                    'as' => 'orders',
                ],
            ],
            ['$unwind' => 'a'],
            ['$unwind' => 'b'],
            [
                '$redact' =>
                [
                    '$cond' => [
                        'if' => ['$lte' => ['$accessLevel', 3]],
                        'then' => '$$KEEP',
                        'else' => '$$REDACT',
                    ],
                ],
            ],
            [
                '$project' =>
                [
                    '_id' => false,
                    'user' => true,
                    'amount' => true,
                    'invoiceAddress' => true,
                    'deliveryAddress' => [
                        '$cond' => [
                            'if' => [
                                '$and' => [
                                    ['$eq' => ['$useAlternateDeliveryAddress', true]],
                                    ['$ne' => ['$deliveryAddress', null]],
                                ],
                            ],
                            'then' => '$deliveryAddress',
                            'else' => '$invoiceAddress',
                        ],
                    ],
                ],
            ],
            [
                '$group' =>
                [
                    '_id' => '$user',
                    'numOrders' => ['$sum' => 1],
                    'amount' => [
                        'total' => ['$sum' => '$amount'],
                        'avg' => ['$avg' => '$amount'],
                    ],
                ],
            ],
            ['$sort' => ['totalAmount' => 0]],
            ['$sort' => ['numOrders' => -1, 'avgAmount' => 1]],
            ['$limit' => 5],
            ['$skip' => 2],
            ['$foo' => 'bar'],
            ['$out' => 'collectionName'],
        ];

        $builder = $this->dm->createAggregationBuilder(BlogPost::class);
        $builder
            ->geoNear($point)
                ->distanceField('distance')
                ->limit(10) // Limit is applied on $geoNear
                ->field('hasCoordinates')
                ->exists(true)
                ->field('username')
                ->equals('foo')
            ->match()
                ->field('group')
                ->in(['a', 'b'])
                ->addOr($builder->matchExpr()->field('username')->equals('admin'))
                ->addOr($builder->matchExpr()->field('username')->equals('administrator'))
            ->sample(10)
            ->lookup('orders')
                ->localField('_id')
                ->foreignField('user.$id')
                ->alias('orders')
            ->unwind('a')
            ->unwind('b')
            ->redact()
                ->cond(
                    $builder->expr()->lte('$accessLevel', 3),
                    '$$KEEP',
                    '$$REDACT',
                )
            ->project()
                ->excludeFields(['_id'])
                ->includeFields(['user', 'amount', 'invoiceAddress'])
                ->field('deliveryAddress')
                ->cond(
                    $builder->expr()
                        ->addAnd($builder->expr()->eq('$useAlternateDeliveryAddress', true))
                        ->addAnd($builder->expr()->ne('$deliveryAddress', null)),
                    '$deliveryAddress',
                    '$invoiceAddress',
                )
            ->group()
                ->field('_id')
                ->expression('$user')
                ->field('numOrders')
                ->sum(1)
                ->field('amount')
                ->expression(
                    $builder->expr()
                        ->field('total')
                        ->sum('$amount')
                        ->field('avg')
                        ->avg('$amount'),
                )
            ->sort('totalAmount')
            ->sort(['numOrders' => 'desc', 'avgAmount' => 'asc']) // Multiple subsequent sorts are combined into a single stage
            ->limit(5)
            ->skip(2)
            ->addStage(new TestStage($builder))
            ->out('collectionName');

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testAggregationBuilder(): void
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(BlogPost::class);

        $resultCursor = $builder
            ->hydrate(BlogTagAggregation::class)
            ->unwind('$tags')
            ->group()
                ->field('id')
                ->expression('$tags')
                ->field('numPosts')
                ->sum(1)
            ->sort('numPosts', 'desc')
            ->execute();

        self::assertInstanceOf(Iterator::class, $resultCursor);

        $results = $resultCursor->toArray();
        self::assertCount(2, $results);
        self::assertInstanceOf(BlogTagAggregation::class, $results[0]);

        self::assertSame('baseball', $results[0]->tag->name);
        self::assertSame(3, $results[0]->numPosts);
    }

    public function testAggregationBuilderResetHydration(): void
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(BlogPost::class)->hydrate(BlogTagAggregation::class);

        $resultCursor = $builder
            ->hydrate(null)
            ->unwind('$tags')
            ->group()
            ->field('id')
            ->expression('$tags')
            ->field('numPosts')
            ->sum(1)
            ->sort('numPosts', 'desc')
            ->getAggregation()
            ->getIterator();

        self::assertInstanceOf(Iterator::class, $resultCursor);

        $results = $resultCursor->toArray();
        self::assertCount(2, $results);
        self::assertIsArray($results[0]);
        self::assertInstanceOf(ObjectId::class, $results[0]['_id']['$id']);
        self::assertSame('Tag', $results[0]['_id']['$ref']);
        self::assertSame(3, $results[0]['numPosts']);
    }

    public function testGetAggregation(): void
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(BlogPost::class);

        $aggregation = $builder
            ->hydrate(BlogTagAggregation::class)
            ->unwind('$tags')
            ->group()
                ->field('id')
                ->expression('$tags')
                ->field('numPosts')
                ->sum(1)
            ->sort('numPosts', 'desc')
            ->getAggregation();

        self::assertInstanceOf(Aggregation::class, $aggregation);

        $resultCursor = $aggregation->getIterator();

        self::assertInstanceOf(Iterator::class, $resultCursor);

        $results = $resultCursor->toArray();
        self::assertCount(2, $results);
        self::assertInstanceOf(BlogTagAggregation::class, $results[0]);

        self::assertSame('baseball', $results[0]->tag->name);
        self::assertSame(3, $results[0]->numPosts);
    }

    public function testPipelineConvertsTypes(): void
    {
        $builder  = $this->dm->createAggregationBuilder(Article::class);
        $dateTime = new DateTimeImmutable('2000-01-01T00:00Z');
        $builder
            ->group()
                ->field('id')
                ->expression(
                    $builder->expr()
                        ->cond(
                            $builder->expr()->lt('$createdAt', $dateTime),
                            true,
                            false,
                        ),
                )
                ->field('numPosts')
                ->sum(1)
            ->replaceRoot()
                ->field('isToday')
                ->eq('$createdAt', $dateTime);

        $expectedPipeline = [
            [
                '$group' => [
                    '_id' => [
                        '$cond' => [
                            'if' => ['$lt' => ['$createdAt', new UTCDateTime((int) $dateTime->format('Uv'))]],
                            'then' => true,
                            'else' => false,
                        ],
                    ],
                    'numPosts' => ['$sum' => 1],
                ],
            ],
            [
                '$replaceRoot' => [
                    'newRoot' => (object) [
                        'isToday' => [
                            '$eq' => ['$createdAt', new UTCDateTime((int) $dateTime->format('Uv'))],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testFieldNameConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);
        $builder
            ->match()
                ->field('authorIp')
                ->notEqual('127.0.0.1')
            ->project()
                ->includeFields(['authorIp'])
            ->unwind('authorIp')
            ->sort('authorIp', 'asc')
            ->replaceRoot('$authorIp');

        $expectedPipeline = [
            [
                '$match' => ['ip' => ['$ne' => '127.0.0.1']],
            ],
            [
                '$project' => ['ip' => true],
            ],
            ['$unwind' => 'ip'],
            [
                '$sort' => ['ip' => 1],
            ],
            ['$replaceRoot' => ['newRoot' => '$ip']],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderAppliesFilterAndDiscriminatorWithMatchStage(): void
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', GuestServer::class);
        $filter->setParameter('field', 'filtered');
        $filter->setParameter('value', true);

        $builder = $this->dm->createAggregationBuilder(GuestServer::class);
        $builder
            ->project()
            ->excludeFields(['_id']);

        $expectedPipeline = [
            [
                '$match' => [
                    '$and' => [
                        ['stype' => 'server_guest'],
                        ['filtered' => true],
                    ],
                ],

            ],
            [
                '$project' => ['_id' => false],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderAppliesFilterAndDiscriminatorWithGeoNearStage(): void
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', GuestServer::class);
        $filter->setParameter('field', 'filtered');
        $filter->setParameter('value', true);

        $builder = $this->dm->createAggregationBuilder(GuestServer::class);
        $builder
            ->geoNear(0, 0);

        $expectedPipeline = [
            [
                '$geoNear' => [
                    'near' => [0, 0],
                    'spherical' => false,
                    'distanceField' => null,
                    'query' => [
                        '$and' => [
                            ['stype' => 'server_guest'],
                            ['filtered' => true],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderWithOutStageReturnsNoData(): void
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(BlogPost::class);
        $builder
            ->out('sampleCollection');

        $result = $builder->execute()->toArray();
        self::assertEmpty($result);
    }

    public function testBuilderWithIndexStatsStageDoesNotApplyFilters(): void
    {
        $builder = $this->dm
            ->createAggregationBuilder(BlogPost::class)
            ->indexStats();

        self::assertSame('$indexStats', array_keys($builder->getPipeline()[0])[0]);
    }

    public function testNonRewindableBuilder(): void
    {
        $builder = $this->dm
            ->createAggregationBuilder(BlogPost::class)
            ->match()
            ->rewindable(false);

        $iterator = $builder->execute();
        self::assertInstanceOf(UnrewindableIterator::class, $iterator);
    }

    private function insertTestData(): void
    {
        $baseballTag = new Tag('baseball');
        $footballTag = new Tag('football');

        $blogPost       = new BlogPost();
        $blogPost->name = 'Test 1';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost       = new BlogPost();
        $blogPost->name = 'Test 2';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost       = new BlogPost();
        $blogPost->name = 'Test 3';
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $blogPost       = new BlogPost();
        $blogPost->name = 'Test 4';
        $blogPost->addTag($baseballTag);
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $this->dm->flush();
        $this->dm->clear();
    }
}

class TestStage extends Stage
{
    public function getExpression(): array
    {
        return ['$foo' => 'bar'];
    }
}
