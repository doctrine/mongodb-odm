<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testThatOrAcceptsAnotherQuery()
    {
        $class = 'Person';
        $expression1 = array('first_name' => 'Kris');
        $expression2 = array('first_name' => 'Chris');

        $dm = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $dm
            ->expects($this->once())
            ->method('find')
            ->with($class, array(
                '$or' => array($expression1, $expression2),
            ))
            ->will($this->returnValue(
                $cursor = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\MongoCursor')
                    ->disableOriginalConstructor()
                    ->getMock()
            ));

        $hydrator = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Hydrator')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $query = new Query($dm, $hydrator, '$', $class);
        $query->addOr($expression1);
        $query->addOr($expression2);

        $this->assertSame($cursor, $query->execute());
    }
}
