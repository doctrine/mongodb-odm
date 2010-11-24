<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\QueryBuilder;

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

        $metadata = $this->getMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadata', array(), array(), '', false, false);
        $metadata->name = $class;
        $dm
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with('Person')
            ->will($this->returnValue($metadata));

        $hydrator = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Hydrator')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $qb = new QueryBuilder($dm, $hydrator, '$', $class);
        $qb->addOr($qb->expr()->field('first_name')->equals('Kris'));
        $qb->addOr($qb->expr()->field('first_name')->equals('Chris'));
        $query = $qb->getQuery();

        $this->assertEquals(array('$or' => array(
            array('first_name' => 'Kris'),
            array('first_name' => 'Chris')
        )), $qb->getQueryArray());
        $this->assertSame($cursor, $query->execute());
    }
}