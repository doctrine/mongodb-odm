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
        $uw = $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $documentPersister = $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();
        $class = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $expected = array('foo.$id' => '1234');

        $dm->expects($this->once())
            ->method('createDBRef')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $dm->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uw));
        $uw->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));
        $documentPersister->expects($this->once())
            ->method('prepareQuery')
            ->with($expected)
            ->will($this->returnValue($expected));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('targetDocument' => 'Foo')));

        $expr = new Expr($dm, '$');
        $expr->setClassMetadata($class);
        $expr->field('foo')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses just $id if a targetDocument is set');
    }

    public function testReferencesUsesAllKeys()
    {
        $dm = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uw = $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $documentPersister = $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();
        $class = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $expected = array('foo.$ref' => 'coll', 'foo.$id' => '1234', 'foo.$db' => 'db');

        $dm->expects($this->once())
            ->method('createDBRef')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $dm->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uw));
        $uw->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));
        $documentPersister->expects($this->once())
            ->method('prepareQuery')
            ->with($expected)
            ->will($this->returnValue($expected));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array()));

        $expr = new Expr($dm, '$');
        $expr->setClassMetadata($class);
        $expr->field('foo')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses all keys if no targetDocument is set');
    }
}