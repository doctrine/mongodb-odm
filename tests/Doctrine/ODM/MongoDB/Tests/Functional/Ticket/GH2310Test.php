<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH2310Test extends BaseTest
{
    public function setUp(): void
    {
        if (\version_compare((string) \phpversion(), '7.4.0', '<')) {
            self::markTestSkipped('PHP 7.4 is required to run this test');
        }

        parent::setUp();
    }

    public function testFindWithNullableEmbedded(): void
    {
        $document = new GH2310Container(10, null);
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(GH2310Container::class);
        $result = $repository->find($document->id);

        self::assertInstanceOf(GH2310Container::class, $result);
        self::assertSame($document->id, $result->id);
        self::assertNull($result->embedded);
    }

    public function testAggregatorBuilderWithNullableEmbedded(): void
    {
        $document = new GH2310Container(10, null);
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
}

/**
 * @ODM\Document()
 */
class GH2310Container
{
    /** @ODM\Id(strategy="none") */
    public int $id;

    /** @ODM\EmbedOne(targetDocument=GH2310Embedded::class, nullable=true) */
    public ?GH2310Embedded $embedded;

    public function __construct(int $id, ?GH2310Embedded $embedded)
    {
        $this->id = $id;
        $this->embedded = $embedded;
    }
}

class GH2310Embedded
{
    /** @ODM\Field(type="integer") */
    public int $value;
}
