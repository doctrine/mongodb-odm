<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Pagination;

use Closure;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Pagination\Paginator;
use Generator;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class PaginatorTest extends TestCase
{
    public function testPaginator(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $paginator
            ->expects($this->once())
            ->method('getResultsForCurrentPage')
            ->willReturnCallback(
                static fn (): Iterator => new UnrewindableIterator(self::createGenerator()()),
            );

        $this->assertSame(['1', '2', '3'], iterator_to_array($paginator->getIterator()));
    }

    /** @param array<array-key, mixed> $values */
    private static function createGenerator(array $values = ['1', '2', '3']): Closure
    {
        return static function () use ($values): Generator {
            yield from $values;
        };
    }
}
