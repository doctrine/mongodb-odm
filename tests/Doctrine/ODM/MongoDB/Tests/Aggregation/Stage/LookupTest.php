<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

class LookupTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testLookupStage()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->lookup('user')
                ->alias('user');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithClassName()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->lookup(\Documents\User::class)
                ->localField('userId')
                ->foreignField('_id')
                ->alias('user');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithCollectionName()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->lookup('randomCollectionName')
                ->localField('userId')
                ->foreignField('_id')
                ->alias('user');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'randomCollectionName',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(0, $result[0]['user']);
    }

    public function testLookupStageReferenceMany()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->unwind('$users')
            ->lookup('users')
                ->alias('users');

        $expectedPipeline = [
            [
                '$unwind' => '$users',
            ],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'users',
                    'foreignField' => '_id',
                    'as' => 'users',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(2, $result);
        $this->assertCount(1, $result[0]['users']);
        $this->assertSame('alcaeus', $result[0]['users'][0]['username']);
        $this->assertCount(1, $result[1]['users']);
        $this->assertSame('malarzm', $result[1]['users'][0]['username']);
    }

    public function testLookupStageReferenceManyWithoutUnwind()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->lookup('users')
                ->alias('users');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'users',
                    'foreignField' => '_id',
                    'as' => 'users',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(0, $result[0]['users']);
    }

    public function testLookupStageReferenceOneInverse()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('simpleReferenceOneInverse')
                ->alias('simpleReferenceOneInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus']
            ],
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'userId',
                    'as' => 'simpleReferenceOneInverse',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['simpleReferenceOneInverse']);
    }

    public function testLookupStageReferenceManyInverse()
    {
        $this->requireMongoDB32();
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder(\Documents\User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('simpleReferenceManyInverse')
                ->alias('simpleReferenceManyInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus']
            ],
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'users',
                    'as' => 'simpleReferenceManyInverse',
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['simpleReferenceManyInverse']);
    }

    private function insertTestData()
    {
        $user1 = new \Documents\User();
        $user1->setUsername('alcaeus');
        $user2 = new \Documents\User();
        $user2->setUsername('malarzm');

        $this->dm->persist($user1);
        $this->dm->persist($user2);

        $simpleReferenceUser = new \Documents\SimpleReferenceUser();
        $simpleReferenceUser->user = $user1;
        $simpleReferenceUser->addUser($user1);
        $simpleReferenceUser->addUser($user2);

        $this->dm->persist($simpleReferenceUser);
        $this->dm->flush();
    }

    private function requireMongoDB32()
    {
        $mongoDbVersion = $this->dm->getConnection()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->command(['serverStatus' => true]);
        if (version_compare($mongoDbVersion['version'], '3.2.0', '<')) {
            $this->markTestSkipped('$lookup tests require at least MongoDB 3.2.0');
        }
    }
}
