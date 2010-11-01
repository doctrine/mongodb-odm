<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

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
        $config = $this->getMock('Doctrine\\ODM\\MongoDB\\Configuration');
        $cursor = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\MongoCursor')
            ->disableOriginalConstructor()
            ->getMock();

        $dm
            ->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($config));
        $config
            ->expects($this->once())
            ->method('getMongoCmd')
            ->will($this->returnValue('$'));
        $dm
            ->expects($this->once())
            ->method('find')
            ->with($class, array(
                '$or' => array($expression1, $expression2),
            ))
            ->will($this->returnValue($cursor));

        $query = new Query($dm, $class);
        $query->addOr($expression1);
        $query->addOr($expression2);

        $this->assertSame($cursor, $query->execute());
    }
}
