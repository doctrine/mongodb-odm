<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Facet;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use InvalidArgumentException;
use LogicException;
use stdClass;

class FacetTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $nestedBuilder = $this->getTestAggregationBuilder();
        $nestedBuilder->sortByCount('$tags');

        $facetStage = new Facet($this->getTestAggregationBuilder());
        $facetStage
            ->field('someField')
            ->pipeline($nestedBuilder)
            ->field('otherField')
            ->pipeline($this->getTestAggregationBuilder()->sortByCount('$comments'));

        self::assertSame([
            '$facet' => [
                'someField' => [['$sortByCount' => '$tags']],
                'otherField' => [['$sortByCount' => '$comments']],
            ],
        ], $facetStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $nestedBuilder = $this->getTestAggregationBuilder();
        $nestedBuilder->sortByCount('$tags');

        $builder = $this->getTestAggregationBuilder();
        $builder->facet()
            ->field('someField')
            ->pipeline($nestedBuilder)
            ->field('otherField')
            ->pipeline($this->getTestAggregationBuilder()->sortByCount('$comments'));

        self::assertSame([
            [
                '$facet' => [
                    'someField' => [['$sortByCount' => '$tags']],
                    'otherField' => [['$sortByCount' => '$comments']],
                ],
            ],
        ], $builder->getPipeline());
    }

    public function testThrowsExceptionWithoutFieldName(): void
    {
        $facetStage = new Facet($this->getTestAggregationBuilder());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires setting a current field using field().');
        $facetStage->pipeline($this->getTestAggregationBuilder());
    }

    public function testThrowsExceptionOnInvalidPipeline(): void
    {
        $facetStage = new Facet($this->getTestAggregationBuilder());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expects either an aggregation builder or an aggregation stage.');
        $facetStage
            ->field('someField')
            ->pipeline(new stdClass());
    }
}
