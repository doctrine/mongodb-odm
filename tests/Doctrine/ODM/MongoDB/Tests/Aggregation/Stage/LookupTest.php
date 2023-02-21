<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\ReferenceUser;
use Documents\SimpleReferenceUser;
use Documents\User;
use InvalidArgumentException;

class LookupTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->insertTestData();
    }

    public function testLookupStage(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['user']);
        self::assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithPipelineAsArray(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->lookup('user')
            ->excludeLocalAndForeignField()
            ->alias('user')
            ->let(['name' => '$username'])
            ->pipeline([
                [
                    '$match' => ['username' => 'alcaeus'],
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'username' => 1,
                    ],
                ],
            ]);

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'as' => 'user',
                    'let' => ['name' => '$username'],
                    'pipeline' => [
                        [
                            '$match' => ['username' => 'alcaeus'],
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'username' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupStageWithPipelineAsStage(): void
    {
        $builder               = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $lookupPipelineBuilder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->lookup('user')
            ->excludeLocalAndForeignField()
            ->alias('user')
            ->let(['name' => '$username'])
            ->pipeline(
                $lookupPipelineBuilder
                    ->match()
                        ->field('username')->equals('alcaeus')
                ->project()
                ->includeFields(['username'])
                ->excludeFields(['_id']),
            );

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'as' => 'user',
                    'let' => ['name' => '$username'],
                    'pipeline' => [
                        [
                            '$match' => ['username' => 'alcaeus'],
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'username' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupThrowsExceptionUsingSameBuilderForPipeline(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);

        $this->expectException(InvalidArgumentException::class);

        $builder
            ->lookup('user')
            ->excludeLocalAndForeignField()
            ->alias('user')
            ->let(['name' => '$username'])
            ->pipeline(
                $builder
                    ->match()
                        ->field('username')->equals('alcaeus'),
            );
    }

    public function testLookupStageWithPipelineAndLocalForeignFields(): void
    {
        $builder               = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $lookupPipelineBuilder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->lookup('user')
            ->alias('user')
            ->let(['name' => '$username'])
            ->pipeline(
                $lookupPipelineBuilder->match()->field('username')->equals('alcaeus')
                    ->project()
                    ->includeFields(['username'])
                    ->excludeFields(['_id']),
            );

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'users',
                    'as' => 'user',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'let' => ['name' => '$username'],
                    'pipeline' => [
                        [
                            '$match' => ['username' => 'alcaeus'],
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'username' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupStageWithFieldNameTranslation(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testLookupStageWithClassName(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['user']);
        self::assertSame('alcaeus', $result[0]['user'][0]['username']);
    }

    public function testLookupStageWithCollectionName(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertEmpty($result[0]['user']);
    }

    public function testLookupStageReferenceMany(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(2, $result);
        self::assertCount(1, $result[0]['users']);
        self::assertSame('alcaeus', $result[0]['users'][0]['username']);
        self::assertCount(1, $result[1]['users']);
        self::assertSame('malarzm', $result[1]['users'][0]['username']);
    }

    public function testLookupStageReferenceManyStoreAsRef(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(2, $result);
        self::assertCount(1, $result[0]['users']);
        self::assertSame('alcaeus', $result[0]['users'][0]['username']);
        self::assertCount(1, $result[1]['users']);
        self::assertSame('malarzm', $result[1]['users'][0]['username']);
    }

    public function testLookupStageReferenceOneInverse(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['simpleReferenceOneInverse']);
    }

    public function testLookupStageReferenceManyInverse(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['simpleReferenceManyInverse']);
    }

    public function testLookupStageReferenceOneInverseStoreAsRef(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['embeddedReferenceOneInverse']);
    }

    public function testLookupStageReferenceManyInverseStoreAsRef(): void
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

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]['embeddedReferenceManyInverse']);
    }

    private function insertTestData(): void
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

    public function testLookupStageAndDefaultAlias(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->lookup('simpleReferenceOneInverse');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'userId',
                    'as' => 'simpleReferenceOneInverse',
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();
        self::assertCount(1, $result[0]['simpleReferenceOneInverse']);
    }

    public function testLookupStageAndDefaultAliasOverride(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->lookup('simpleReferenceOneInverse')
                ->alias('override');

        $expectedPipeline = [
            [
                '$lookup' => [
                    'from' => 'SimpleReferenceUser',
                    'localField' => '_id',
                    'foreignField' => 'userId',
                    'as' => 'override',
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();
        self::assertCount(1, $result[0]['override']);
    }
}
