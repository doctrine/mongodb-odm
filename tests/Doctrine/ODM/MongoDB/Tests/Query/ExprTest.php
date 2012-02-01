<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\Expr;

class ExprTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testInIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->in($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['groups.$id']['$in']));
    }

    public function testAllIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->all($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['groups.$id']['$all']));
    }

    public function testNotEqualIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notEqual($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['groups.$id']['$ne']));
    }

    public function testNotInIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notIn($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['groups.$id']['$nin']));
    }

    public function testAndIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addAnd($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['$and'][0]['groups.$id']['$in']));
    }

    public function testOrIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addOr($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals($id, (string) current($debug['$or'][0]['groups.$id']['$in']));
    }

    public function testPrepareNestedDocuments()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('address.subAddress.subAddress.subAddress.test')->equals('test');
        $query = $qb->getQuery();
        $debug = $query->debug();
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => 'test'), $debug);
    }

    public function testSortIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->sort('id', 'desc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('_id' => -1), $query['sort']);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->sort('address.subAddress.subAddress.subAddress.test', 'asc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => 1), $query['sort']);
    }

    public function testNestedWithOperator()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('address.subAddress.subAddress.subAddress.test')->notIn('test');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => array('$nin' => array('test'))), $query['query']);
    }

    public function testNewObjectIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->field('address.subAddress.subAddress.subAddress.test')->popFirst();
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('$pop' => array('address.subAddress.subAddress.subAddress.testFieldName' => 1)), $query['newObj']);
    }

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