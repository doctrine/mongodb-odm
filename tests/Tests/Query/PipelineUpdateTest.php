<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use MongoDB\Builder\Expression;
use MongoDB\Builder\Pipeline;
use MongoDB\Builder\Stage;

class PipelineUpdateTest extends BaseTestCase
{
    private User $user1;
    private User $user2;

    public function setUp(): void
    {
        parent::setUp();

        $this->user1 = new User();
        $this->user1->setUsername('foo');
        $this->user1->setHits(1);

        $this->user2 = new User();
        $this->user2->setUsername('bar');
        $this->user2->setHits(2);

        $this->dm->persist($this->user1);
        $this->dm->persist($this->user2);

        $this->dm->flush();
    }

    public function testUpdateManyWithWrongType(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('The pipeline() method can only be used with update or findAndUpdate queries.');

        $this->dm->createQueryBuilder(User::class)
            ->pipeline([]);
    }

    public function testUpdateOneWithAggregationBuilder(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->set()
                ->field('hits')
                ->add('$hits', 1);

        $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('username')->equals('foo')
            ->pipeline($builder)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(2, $user2->getHits());
    }

    public function testUpdateOneWithDriverPipeline(): void
    {
        $pipeline = new Pipeline(
            Stage::set(
                hits: Expression::add(Expression::fieldPath('hits'), 1),
            ),
        );

        $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('username')->equals('foo')
            ->pipeline($pipeline)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(2, $user2->getHits());
    }

    public function testUpdateOneWithPipelineArray(): void
    {
        $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('username')->equals('foo')
            ->pipeline([['$set' => ['hits' => ['$sum' => ['$hits', 1]]]]])
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(2, $user2->getHits());
    }

    public function testUpdateManyWithAggregationBuilder(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->set()
                ->field('hits')
                ->multiply('$hits', 2);

        $this->dm->createQueryBuilder(User::class)
            ->updateMany()
            ->pipeline($builder)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(4, $user2->getHits());
    }

    public function testUpdateManyWithDriverPipeline(): void
    {
        $pipeline = new Pipeline(
            Stage::set(
                hits: Expression::multiply(Expression::fieldPath('hits'), 2),
            ),
        );

        $this->dm->createQueryBuilder(User::class)
            ->updateMany()
            ->pipeline($pipeline)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(4, $user2->getHits());
    }

    public function testUpdateManyWithPipelineArray(): void
    {
        $this->dm->createQueryBuilder(User::class)
            ->updateMany()
            ->pipeline([['$set' => ['hits' => ['$multiply' => ['$hits', 2]]]]])
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $user1 = $this->dm->getRepository(User::class)->find($this->user1->getId());
        self::assertSame(2, $user1->getHits());

        $user2 = $this->dm->getRepository(User::class)->find($this->user2->getId());
        self::assertSame(4, $user2->getHits());
    }

    public function testFindOneAndUpdateWithAggregationBuilder(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);
        $builder
            ->set()
                ->field('hits')
                ->add('$hits', 1);

        $user = $this->dm->createQueryBuilder(User::class)
            ->findAndUpdate()
            ->returnNew()
            ->field('username')->equals('foo')
            ->pipeline($builder)
            ->getQuery()
            ->execute();

        self::assertInstanceOf(User::class, $user);
        self::assertSame(2, $user->getHits());
    }

    public function testFindOneAndUpdateWithDriverPipeline(): void
    {
        $this->markTestSkipped('Collection::findAndUpdate does not support pipeline updates (PHPLIB-1699)');

        $pipeline = new Pipeline(
            Stage::set(
                hits: Expression::add(Expression::fieldPath('hits'), 1),
            ),
        );

        $user = $this->dm->createQueryBuilder(User::class)
            ->findAndUpdate()
            ->returnNew()
            ->field('username')->equals('foo')
            ->pipeline($pipeline)
            ->getQuery()
            ->execute();

        self::assertInstanceOf(User::class, $user);
        self::assertSame(2, $user->getHits());
    }

    public function testFindOneAndUpdateWithPipelineArray(): void
    {
        $user = $this->dm->createQueryBuilder(User::class)
            ->findAndUpdate()
            ->returnNew()
            ->field('username')->equals('foo')
            ->pipeline([['$set' => ['hits' => ['$sum' => ['$hits', 1]]]]])
            ->getQuery()
            ->execute();

        self::assertInstanceOf(User::class, $user);
        self::assertSame(2, $user->getHits());
    }
}
