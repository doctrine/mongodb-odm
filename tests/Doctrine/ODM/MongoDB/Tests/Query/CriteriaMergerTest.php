<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use PHPUnit\Framework\TestCase;

use function call_user_func_array;

class CriteriaMergerTest extends TestCase
{
    /**
     * @param array<array<string, mixed>> $args
     * @param array<string, mixed>        $merged
     *
     * @dataProvider provideMerge
     */
    public function testMerge(array $args, array $merged): void
    {
        self::assertSame($merged, call_user_func_array([new CriteriaMerger(), 'merge'], $args));
    }

    public function provideMerge(): array
    {
        return [
            'no args' => [
                [],
                [],
            ],
            'one arg returned as-is' => [
                [['x' => 1]],
                ['x' => 1],
            ],
            'empty criteria arrays are ignored' => [
                [['x' => 1], []],
                ['x' => 1],
            ],
            'two identical args' => [
                [['x' => 1], ['x' => 1]],
                ['$and' => [['x' => 1], ['x' => 1]]],
            ],
            'two different args' => [
                [['x' => 1], ['y' => 1]],
                ['$and' => [['x' => 1], ['y' => 1]]],
            ],
            'two conflicting args' => [
                [['x' => 1], ['x' => 2]],
                ['$and' => [['x' => 1], ['x' => 2]]],
            ],
            'existing $and criteria gets nested' => [
                [['$and' => [['x' => 1]]], ['x' => 1]],
                ['$and' => [['$and' => [['x' => 1]]], ['x' => 1]]],
            ],
        ];
    }
}
