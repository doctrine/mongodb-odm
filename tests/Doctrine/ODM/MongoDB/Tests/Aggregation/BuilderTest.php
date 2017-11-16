<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

class BuilderTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetPipeline()
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
                    'num' => 10
                ]
            ],
            ['$match' =>
                [
                    '$or' => [
                        ['username' => 'admin'],
                        ['username' => 'administrator']
                    ],
                    'group' => ['$in' => ['a', 'b']]
                ]
            ],
            ['$sample' => ['size' => 10]],
            ['$lookup' =>
                [
                    'from' => 'orders',
                    'localField' => '_id',
                    'foreignField' => 'user.$id',
                    'as' => 'orders'
                ]
            ],
            ['$unwind' => 'a'],
            ['$unwind' => 'b'],
            ['$redact' =>
                [
                    '$cond' => [
                        'if' => ['$lte' => ['$accessLevel', 3]],
                        'then' => '$$KEEP',
                        'else' => '$$REDACT',
                    ]
                ]
            ],
            ['$project' =>
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
                                    ['$ne' => ['$deliveryAddress', null]]
                                ]
                            ],
                            'then' => '$deliveryAddress',
                            'else' => '$invoiceAddress'
                        ]
                    ]
                ]
            ],
            ['$group' =>
                [
                    '_id' => '$user',
                    'numOrders' => ['$sum' => 1],
                    'amount' => [
                        'total' => ['$sum' => '$amount'],
                        'avg' => ['$avg' => '$amount']
                    ]
                ]
            ],
            ['$sort' => ['totalAmount' => 0]],
            ['$sort' => ['numOrders' => -1, 'avgAmount' => 1]],
            ['$limit' => 5],
            ['$skip' => 2],
            ['$out' => 'collectionName']
        ];

        $builder = $this->dm->createAggregationBuilder(\Documents\BlogPost::class);
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
                    '$$REDACT'
                )
            ->project()
                ->excludeIdField()
                ->includeFields(['user', 'amount', 'invoiceAddress'])
                ->field('deliveryAddress')
                ->cond(
                    $builder->expr()
                        ->addAnd($builder->expr()->eq('$useAlternateDeliveryAddress', true))
                        ->addAnd($builder->expr()->ne('$deliveryAddress', null)),
                    '$deliveryAddress',
                    '$invoiceAddress'
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
                        ->avg('$amount')
                )
            ->sort('totalAmount')
            ->sort(['numOrders' => 'desc', 'avgAmount' => 'asc']) // Multiple subsequent sorts are combined into a single stage
            ->limit(5)
            ->skip(2)
            ->out('collectionName');

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testAggregationBuilder()
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\BlogPost::class);

        $resultCursor = $builder
            ->hydrate(\Documents\BlogTagAggregation::class)
            ->unwind('$tags')
            ->group()
                ->field('id')
                ->expression('$tags')
                ->field('numPosts')
                ->sum(1)
            ->sort('numPosts', 'desc')
            ->execute();

        $this->assertInstanceOf(\Doctrine\ODM\MongoDB\Cursor::class, $resultCursor);

        $results = $resultCursor->toArray();
        $this->assertCount(2, $results);
        $this->assertInstanceOf(\Documents\BlogTagAggregation::class, $results[0]);

        $this->assertSame('baseball', $results[0]->tag->name);
        $this->assertSame(3, $results[0]->numPosts);
    }

    public function testPipelineConvertsTypes()
    {
        $builder = $this->dm->createAggregationBuilder(\Documents\Article::class);
        $dateTime = new \DateTimeImmutable('2000-01-01T00:00Z');
        $builder
            ->group()
                ->field('id')
                ->expression(
                    $builder->expr()
                        ->cond(
                            $builder->expr()->lt('$createdAt', $dateTime),
                            true,
                            false
                        )
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
                            'if' => ['$lt' => ['$createdAt', new \MongoDB\BSON\UTCDateTime((int) $dateTime->format('Uv'))]],
                            'then' => true,
                            'else' => false,
                        ]
                    ],
                    'numPosts' => ['$sum' => 1],
                ]
            ],
            [
                '$replaceRoot' => [
                    'isToday' => ['$eq' => ['$createdAt', new \MongoDB\BSON\UTCDateTime((int) $dateTime->format('Uv'))]],
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testFieldNameConversion()
    {
        $builder = $this->dm->createAggregationBuilder(\Documents\CmsComment::class);
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
            [
                '$unwind' => 'ip',
            ],
            [
                '$sort' => ['ip' => 1],
            ],
            [
                '$replaceRoot' => '$ip',
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderAppliesFilterAndDiscriminatorWithMatchStage()
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', \Documents\GuestServer::class);
        $filter->setParameter('field', 'filtered');
        $filter->setParameter('value', true);

        $builder = $this->dm->createAggregationBuilder(\Documents\GuestServer::class);
        $builder
            ->project()
            ->excludeIdField();

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
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderAppliesFilterAndDiscriminatorWithGeoNearStage()
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', \Documents\GuestServer::class);
        $filter->setParameter('field', 'filtered');
        $filter->setParameter('value', true);

        $builder = $this->dm->createAggregationBuilder(\Documents\GuestServer::class);
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

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testBuilderWithOutStageReturnsNoData()
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\BlogPost::class);
        $builder
            ->out('sampleCollection');

        $result = $builder->execute()->toArray();
        $this->assertCount(0, $result);
    }

    private function insertTestData()
    {
        $baseballTag = new \Documents\Tag('baseball');
        $footballTag = new \Documents\Tag('football');

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 1';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 2';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 3';
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 4';
        $blogPost->addTag($baseballTag);
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $this->dm->flush();
        $this->dm->clear();
    }
}
