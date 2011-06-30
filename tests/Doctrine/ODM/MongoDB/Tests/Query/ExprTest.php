<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\Expr;

class ExprTest extends \PHPUnit_Framework_TestCase
{
    public function testReferencesUsesMinimalKeys()
    {
        $dm = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $class = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $dm->expects($this->once())
            ->method('createDBRef')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('targetDocument' => 'Foo')));

        $expr = new Expr($dm, '$');
        $expr->setClassMetadata($class);
        $expr->field('foo')->references(new \stdClass());

        $this->assertEquals(array('foo.$id' => '1234'), $expr->getQuery(), '->references() uses just $id if a targetDocument is set');
    }

    public function testReferencesUsesAllKeys()
    {
        $dm = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $class = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $dm->expects($this->once())
            ->method('createDBRef')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array()));

        $expr = new Expr($dm, '$');
        $expr->setClassMetadata($class);
        $expr->field('foo')->references(new \stdClass());

        $this->assertEquals(array('foo.$ref' => 'coll', 'foo.$id' => '1234', 'foo.$db' => 'db'), $expr->getQuery(), '->references() uses all keys if no targetDocument is set');
    }
}
