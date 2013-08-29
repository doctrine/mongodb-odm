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
        $this->assertSame($merged, call_user_func_array(array(new CriteriaMerger(), 'merge'), $args));
    }

    public function provideMerge()
    {
        return array(
            'no args' => array(
                array(),
                array(),
            ),
            'one arg returned as-is' => array(
                array(array('x' => 1)),
                array('x' => 1),
            ),
            'empty criteria arrays are ignored' => array(
                array(array('x' => 1), array()),
                array('x' => 1),
            ),
            'two identical args' => array(
                array(array('x' => 1), array('x' => 1)),
                array('$and' => array(array('x' => 1), array('x' => 1))),
            ),
            'two different args' => array(
                array(array('x' => 1), array('y' => 1)),
                array('$and' => array(array('x' => 1), array('y' => 1))),
            ),
            'two conflicting args' => array(
                array(array('x' => 1), array('x' => 2)),
                array('$and' => array(array('x' => 1), array('x' => 2))),
            ),
            'existing $and criteria gets nested' => array(
                array(array('$and' => array(array('x' => 1))), array('x' => 1)),
                array('$and' => array(array('$and' => array(array('x' => 1))), array('x' => 1))),
            ),
        );
    }
}
