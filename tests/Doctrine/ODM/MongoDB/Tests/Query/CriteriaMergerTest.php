<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\CriteriaMerger;

class CriteriaMergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideMerge
     */
    public function testMerge(array $args, array $merged)
    {
        $this->assertSame($merged, call_user_func_array([new CriteriaMerger(), 'merge'], $args));
    }

    public function provideMerge()
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
