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
    /**
     * @param array<string, mixed> $expected
     * @param list<string>         $allowedMetaSort
     */
    #[DataProvider('provideValidSortOrders')]
    public function testNormalizeSortDirections(array $expected, mixed $order, array $allowedMetaSort = []): void
    {
        self::assertSame($expected, SortHelper::normalizeSortDirections(['field' => $order], $allowedMetaSort));
    }

    public static function provideValidSortOrders(): iterable
    {
        yield 'ascending int' => [['field' => 1], 1];
        yield 'descending int' => [['field' => -1], -1];
        yield 'asc' => [['field' => 1], 'asc'];
        yield 'ASC' => [['field' => 1], 'ASC'];
        yield 'Asc' => [['field' => 1], 'Asc'];
        yield 'desc' => [['field' => -1], 'desc'];
        yield 'DESC' => [['field' => -1], 'DESC'];
        yield 'Desc' => [['field' => -1], 'Desc'];
        yield 'ascending enum' => [['field' => 1], SortDirection::Ascending];
        yield 'descending enum' => [['field' => -1], SortDirection::Descending];
        yield 'float ascending' => [['field' => 1], 1.0];
        yield 'float descending' => [['field' => -1], -1.0];
        yield 'allowed meta keyword' => [['field' => ['$meta' => 'textScore']], 'textScore', ['textScore', 'searchScore']];
        yield 'allowed searchScore meta keyword' => [['field' => ['$meta' => 'searchScore']], 'searchScore', ['textScore', 'searchScore']];
        yield 'meta expression' => [['field' => ['$meta' => 'searchScore']], ['$meta' => 'searchScore'], ['textScore', 'searchScore']];
    }

    #[DataProvider('provideInvalidSortOrders')]
    public function testNormalizeSortDirectionsRejectsInvalidValues(mixed $order): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('for field "field"');

        SortHelper::normalizeSortDirections(['field' => $order]);
    }

    public static function provideInvalidSortOrders(): iterable
    {
        yield 'zero' => [0];
        yield 'numeric string zero' => ['0'];
        yield 'numeric string ascending' => ['1'];
        yield 'numeric string descending' => ['-1'];
        yield 'out of range int' => [2];
        yield 'large integer' => [12];
        yield 'decimal float' => [1.1];
        yield 'decimal string' => ['1.5'];
        yield 'unknown keyword' => ['nonExistingMetaField'];
        yield 'meta keyword not allowed' => ['textScore'];
        yield 'empty array' => [[]];
        yield 'array without meta key' => [['bogus' => 'nonsense']];
        yield 'array with unknown meta keyword' => [['$meta' => 'nonExistentScore']];
        yield 'array with disallowed meta keyword' => [['$meta' => 'textScore']];
        yield 'null' => [null];
        yield 'boolean' => [true];
        yield 'object' => [new stdClass()];
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
