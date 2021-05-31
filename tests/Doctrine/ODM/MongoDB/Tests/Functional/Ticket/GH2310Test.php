<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents74\GH2310Container;
use Documents74\GH2310Embedded;
use MongoDB\BSON\ObjectId;

use function phpversion;
use function version_compare;

class GH2310Test extends BaseTest
{
    public function setUp(): void
    {
        if (version_compare((string) phpversion(), '7.4.0', '<')) {
            self::markTestSkipped('PHP 7.4 is required to run this test');
        }

        parent::setUp();
    }

    public function testFindWithNullableEmbeddedAfterUpsert(): void
    {
        $document = new GH2310Container((string) new ObjectId(), null);
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(GH2310Container::class);
        $result     = $repository->find($document->id);

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testAggregatorBuilderWithNullableEmbeddedAfterUpsert(): void
    {
        $document = new GH2310Container((string) new ObjectId(), null);
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $aggBuilder = $this->dm->createAggregationBuilder(GH2310Container::class);
        $aggBuilder->match()->field('id')->equals($document->id);
        $result = $aggBuilder->hydrate(GH2310Container::class)->getAggregation()->getIterator()->current();

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testFindWithNullableEmbeddedAfterInsert(): void
    {
        $document = new GH2310Container(null, null);
        $this->dm->persist($document);
        $this->dm->flush();

        $repository = $this->dm->getRepository(GH2310Container::class);
        $result     = $repository->find($document->id);

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testAggregatorBuilderWithNullableEmbeddedAfterInsert(): void
    {
        $document = new GH2310Container(null, null);
        $this->dm->persist($document);
        $this->dm->flush();

        $aggBuilder = $this->dm->createAggregationBuilder(GH2310Container::class);
        $aggBuilder->match()->field('id')->equals($document->id);
        $result = $aggBuilder->hydrate(GH2310Container::class)->getAggregation()->getIterator()->current();

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testFindWithNullableEmbeddedAfterUpdate(): void
    {
        $document = new GH2310Container(null, new GH2310Embedded());
        $this->dm->persist($document);
        $this->dm->flush();

        // Update embedded document to trigger nullable behaviour
        $document->embedded = null;
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(GH2310Container::class);
        $result     = $repository->find($document->id);

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testAggregatorBuilderWithNullableEmbeddedAfterUpdate(): void
    {
        $document = new GH2310Container(null, new GH2310Embedded());
        $this->dm->persist($document);
        $this->dm->flush();

        // Update embedded document to trigger nullable behaviour
        $document->embedded = null;
        $this->dm->flush();
        $this->dm->clear();

        $aggBuilder = $this->dm->createAggregationBuilder(GH2310Container::class);
        $aggBuilder->match()->field('id')->equals($document->id);
        $result = $aggBuilder->hydrate(GH2310Container::class)->getAggregation()->getIterator()->current();

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }
}
