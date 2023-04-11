<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use DateTime;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use MongoDB\BSON\UTCDateTime;

class MatchStageTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $matchStage = new MatchStage($this->getTestAggregationBuilder());
        $matchStage
            ->field('someField')
            ->equals('someValue');

        self::assertSame(['$match' => ['someField' => 'someValue']], $matchStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->match()
            ->field('someField')
            ->equals('someValue');

        self::assertSame([['$match' => ['someField' => 'someValue']]], $builder->getPipeline());
    }

    public function testTypeConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $date      = new DateTime();
        $mongoDate = new UTCDateTime((int) $date->format('Uv'));
        $stage     = $builder
            ->match()
                ->field('createdAt')
                ->lte($date);

        self::assertEquals(
            [
                '$match' => [
                    'createdAt' => ['$lte' => $mongoDate],
                ],
            ],
            $stage->getExpression(),
        );
    }
}
