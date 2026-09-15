<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Utility;

use Doctrine\ODM\MongoDB\Utility\SortHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SortDirection;
use stdClass;

class SortHelperTest extends TestCase
{
    /** @param array<string, mixed> $expected */
    #[DataProvider('provideValidSortOrders')]
    public function testNormalizeSortDirections(array $expected, mixed $order, array $allowedMetaSort = []): void
    {
        self::assertSame($expected, SortHelper::normalizeSortDirections(['field' => $order], $allowedMetaSort));
    }

    public static function provideValidSortOrders(): array
    {
        return [
            'ascending int' => [['field' => 1], 1],
            'descending int' => [['field' => -1], -1],
            'asc' => [['field' => 1], 'asc'],
            'ASC' => [['field' => 1], 'ASC'],
            'desc' => [['field' => -1], 'desc'],
            'DESC' => [['field' => -1], 'DESC'],
            'ascending enum' => [['field' => 1], SortDirection::Ascending],
            'descending enum' => [['field' => -1], SortDirection::Descending],
            'numeric string ascending' => [['field' => 1], '1'],
            'numeric string descending' => [['field' => -1], '-1'],
            'float ascending' => [['field' => 1], 1.0],
            'float descending' => [['field' => -1], -1.0],
            'allowed meta keyword' => [['field' => ['$meta' => 'textScore']], 'textScore', ['textScore', 'indexKey']],
            'allowed indexKey meta keyword' => [['field' => ['$meta' => 'indexKey']], 'indexKey', ['textScore', 'indexKey']],
            'meta expression' => [['field' => ['$meta' => 'searchScore']], ['$meta' => 'searchScore']],
        ];
    }

    #[DataProvider('provideInvalidSortOrders')]
    public function testNormalizeSortDirectionsRejectsInvalidValues(mixed $order): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('for field "field"');

        SortHelper::normalizeSortDirections(['field' => $order]);
    }

    public static function provideInvalidSortOrders(): array
    {
        return [
            'zero' => [0],
            'numeric string zero' => ['0'],
            'out of range int' => [2],
            'unknown keyword' => ['nonExistingMetaField'],
            'meta keyword not allowed' => ['textScore'],
            'null' => [null],
            'boolean' => [true],
            'object' => [new stdClass()],
        ];
    }

    public function testNormalizeSortDirectionsPreservesIntegerLikeFieldNames(): void
    {
        self::assertSame(
            [2024 => -1, 2025 => 1],
            // @phpstan-ignore argument.type (integer-like string keys become integer keys in PHP)
            SortHelper::normalizeSortDirections(['2024' => 'desc', '2025' => 'asc']),
        );
    }
}
