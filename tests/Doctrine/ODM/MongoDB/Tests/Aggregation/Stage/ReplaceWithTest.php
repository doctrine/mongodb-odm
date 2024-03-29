<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsComment;
use Documents\User;
use MongoDB\BSON\UTCDateTime;

class ReplaceWithTest extends BaseTestCase
{
    public function testTypeConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $dateTime  = new DateTimeImmutable('2000-01-01T00:00Z');
        $mongoDate = new UTCDateTime($dateTime);
        $stage     = $builder
            ->replaceWith()
                ->field('isToday')
                ->eq('$createdAt', $dateTime);

        self::assertEquals(
            [
                '$replaceWith' => [
                    'isToday' => ['$eq' => ['$createdAt', $mongoDate]],
                ],
            ],
            $stage->getExpression(),
        );
    }

    public function testTypeConversionWithDirectExpression(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $dateTime  = new DateTimeImmutable('2000-01-01T00:00Z');
        $mongoDate = new UTCDateTime($dateTime);
        $stage     = $builder
            ->replaceWith(
                $builder->expr()
                    ->field('isToday')
                    ->eq('$createdAt', $dateTime),
            );

        self::assertEquals(
            [
                '$replaceWith' => [
                    'isToday' => ['$eq' => ['$createdAt', $mongoDate]],
                ],
            ],
            $stage->getExpression(),
        );
    }

    public function testFieldNameConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $stage = $builder
            ->replaceWith()
                ->field('someField')
                ->concat('$authorIp', 'foo');

        self::assertEquals(
            [
                '$replaceWith' => [
                    'someField' => ['$concat' => ['$ip', 'foo']],
                ],
            ],
            $stage->getExpression(),
        );
    }

    public function testFieldNameConversionWithDirectExpression(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $stage = $builder
            ->replaceWith('$authorIp');

        self::assertEquals(
            ['$replaceWith' => '$ip'],
            $stage->getExpression(),
        );
    }
}
