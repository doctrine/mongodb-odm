<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\ReferenceUser;
use Documents\Sharded\ShardedOne;
use Documents\SimpleReferenceUser;
use Documents\User;

class LookupTest extends BaseTest
{
    public function setUp() : void
    {
        parent::setUp();
        $this->insertTestData();
    }

    public function testLookupStage()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
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
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithFieldNameTranslation()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup(CmsComment::class)
                ->localField('id')
                ->foreignField('authorIp')
                ->alias('user');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'CmsComment',
                    'localField' => '_id',
                    'foreignField' => 'ip',
                    'as' => 'user',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupStageWithClassName()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup(User::class)
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
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithCollectionName()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
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
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(0, $result[0]['user']);
    }

    public function testLookupStageReferenceMany()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->unwind('$users')
            ->lookup('users')
                ->alias('users');

        $expectedPipeline = [
            ['$unwind' => '$users'],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'users',
                    'foreignField' => '_id',
                    'as' => 'users',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(2, $result);
        $this->assertCount(1, $result[0]['users']);
        $this->assertSame('alcaeus', $result[0]['users'][0]['username']);
        $this->assertCount(1, $result[1]['users']);
        $this->assertSame('malarzm', $result[1]['users'][0]['username']);
    }

    public function testLookupStageReferenceManyStoreAsRef()
    {
        $builder = $this->dm->createAggregationBuilder(ReferenceUser::class);
        $builder
            ->unwind('$referencedUsers')
            ->lookup('referencedUsers')
                ->alias('users');

        $expectedPipeline = [
            ['$unwind' => '$referencedUsers'],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'referencedUsers.id',
                    'foreignField' => '_id',
                    'as' => 'users',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(2, $result);
        $this->assertCount(1, $result[0]['users']);
        $this->assertSame('alcaeus', $result[0]['users'][0]['username']);
        $this->assertCount(1, $result[1]['users']);
        $this->assertSame('malarzm', $result[1]['users'][0]['username']);
    }

    public function testLookupStageReferenceManyWithoutUnwindMongoDB34()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
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
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]['users']);
        $this->assertSame('alcaeus', $result[0]['users'][0]['username']);
        $this->assertSame('malarzm', $result[0]['users'][1]['username']);
    }

    public function testLookupStageReferenceOneInverse()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('simpleReferenceOneInverse')
                ->alias('simpleReferenceOneInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus'],
            ],
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'userId',
                    'as' => 'simpleReferenceOneInverse',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['simpleReferenceOneInverse']);
    }

    public function testLookupStageReferenceManyInverse()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('simpleReferenceManyInverse')
                ->alias('simpleReferenceManyInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus'],
            ],
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'users',
                    'as' => 'simpleReferenceManyInverse',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['simpleReferenceManyInverse']);
    }

    public function testLookupStageReferenceOneInverseStoreAsRef()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('embeddedReferenceOneInverse')
                ->alias('embeddedReferenceOneInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus'],
            ],
            [
                '$lookup' => [
                    'from' => 'ReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'referencedUser.id',
                    'as' => 'embeddedReferenceOneInverse',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['embeddedReferenceOneInverse']);
    }

    public function testLookupStageReferenceManyInverseStoreAsRef()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->match()
                ->field('username')
                ->equals('alcaeus')
            ->lookup('embeddedReferenceManyInverse')
                ->alias('embeddedReferenceManyInverse');

        $expectedPipeline = [
            [
                '$match' => ['username' => 'alcaeus'],
            ],
            [
                '$lookup' => [
                    'from' => 'ReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'referencedUsers.id',
                    'as' => 'embeddedReferenceManyInverse',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['embeddedReferenceManyInverse']);
    }

    public function testLookupToShardedCollectionThrowsException()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $this->expectException(MappingException::class);
        $builder
            ->lookup(ShardedOne::class)
                ->localField('id')
                ->foreignField('id');
    }

    public function testLookupToShardedReferenceThrowsException()
    {
        $builder = $this->dm->createAggregationBuilder(ShardedOne::class);

        $this->expectException(MappingException::class);
        $builder
            ->lookup('user');
    }

    private function insertTestData()
    {
        $user1 = new User();
        $user1->setUsername('alcaeus');
        $user2 = new User();
        $user2->setUsername('malarzm');
        $user3 = new User();
        $user3->setUsername('jmikola');

        $this->dm->persist($user1);
        $this->dm->persist($user2);

        $simpleReferenceUser       = new SimpleReferenceUser();
        $simpleReferenceUser->user = $user1;
        $simpleReferenceUser->addUser($user1);
        $simpleReferenceUser->addUser($user2);

        $referenceUser = new ReferenceUser();
        $referenceUser->setReferencedUser($user1);
        $referenceUser->addReferencedUser($user1);
        $referenceUser->addReferencedUser($user2);

        $this->dm->persist($simpleReferenceUser);
        $this->dm->persist($referenceUser);
        $this->dm->flush();
    }

    public function testLookupStageAndDefaultAlias()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup('user');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithFieldNameTranslationAndDefaultAlias()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup(CmsComment::class)
            ->localField('id')
            ->foreignField('authorIp');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'CmsComment',
                    'localField' => '_id',
                    'foreignField' => 'ip',
                    'as' => 'user',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupStageWithClassNameAndDefaultAlias()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup(User::class)
            ->localField('userId')
            ->foreignField('_id');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['user']);
        $this->assertSame('alcaeus', $result[0]['user'][0]['username']);
    }
}
