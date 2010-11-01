<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testThatOrAcceptsAnotherQuery()
    {
        $class = 'Person';

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
            ->with('Person', array(
                '$or' => array(
                    array('first_name' => 'Kris'),
                    array('first_name' => 'Chris'),
                ),
            ))
            ->will($this->returnValue($cursor));

        $query = new Query($dm, $class);
        $query->addOr(array('first_name' => 'Kris'));
        $query->addOr(array('first_name' => 'Chris'));

        $this->assertSame($cursor, $query->execute());
    }
}
