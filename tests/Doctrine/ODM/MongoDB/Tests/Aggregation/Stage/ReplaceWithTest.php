<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\User;
use MongoDB\BSON\UTCDateTime;

class ReplaceWithTest extends BaseTest
{
    public function testTypeConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $dateTime  = new DateTimeImmutable('2000-01-01T00:00Z');
        $mongoDate = new UTCDateTime((int) $dateTime->format('Uv'));
        $stage     = $builder
            ->replaceWith()
                ->field('isToday')
                ->eq('$createdAt', $dateTime);

        self::assertEquals(
            [
                '$replaceWith' => (object) [
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
        $mongoDate = new UTCDateTime((int) $dateTime->format('Uv'));
        $stage     = $builder
            ->replaceWith(
                $builder->expr()
                    ->field('isToday')
                    ->eq('$createdAt', $dateTime),
            );

        self::assertEquals(
            [
                '$replaceWith' => (object) [
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
                '$replaceWith' => (object) [
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
