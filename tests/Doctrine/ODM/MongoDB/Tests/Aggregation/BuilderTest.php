<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

class BuilderTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAggregationBuilder()
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\BlogPost::class);

        $aggregationResult = $builder
            ->hydrate(\Documents\BlogTagAggregation::class)
            ->unwind('$tags')
            ->group()
                ->field('id')
                ->expression('$tags')
                ->field('numPosts')
                ->sum(1)
            ->sort('numPosts', 'desc')
            ->execute();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\CommandCursor', $aggregationResult);
        $this->assertCount(2, $aggregationResult);

        $results = $aggregationResult->toArray();
        $this->assertInstanceOf('Documents\BlogTagAggregation', $results[0]);

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
                            'if' => ['$lt' => ['$createdAt', new \MongoDate($dateTime->format('U'), $dateTime->format('u'))]],
                            'then' => true,
                            'else' => false,
                        ]
                    ],
                    'numPosts' => ['$sum' => 1],
                ]
            ],
            [
                '$replaceRoot' => [
                    'isToday' => ['$eq' => ['$createdAt', new \MongoDate($dateTime->format('U'), $dateTime->format('u'))]],
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

        $result = $builder->execute();
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
