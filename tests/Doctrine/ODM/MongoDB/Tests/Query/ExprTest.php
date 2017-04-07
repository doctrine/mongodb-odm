<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Query\Expr;

class ExprTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSelectIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->select('id');
        $query = $qb->getQuery();

        $this->assertEquals(array('_id' => 1), $query->debug('select'));
    }

    public function testInIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->in($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$in'][0]);
    }

    public function testAllIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->all($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$all'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$all'][0]);
    }

    public function testNotEqualIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notEqual($id)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$ne']);
        $this->assertEquals($id, (string) $debug['groups.$id']['$ne']);
    }

    public function testNotInIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notIn($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testAndIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addAnd($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['$and'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$and'][0]['groups.$id']['$in'][0]);
    }

    public function testOrIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addOr($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['$or'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$or'][0]['groups.$id']['$in'][0]);
    }

    public function testMultipleQueryOperatorsArePrepared()
    {
        $all = array('4f28aa84acee41388900000a');
        $in = array('4f28aa84acee41388900000b');
        $ne = '4f28aa84acee41388900000c';
        $nin = array('4f28aa84acee41388900000d');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->all($all)
            ->field('groups.id')->in($in)
            ->field('groups.id')->notEqual($ne)
            ->field('groups.id')->notIn($nin)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$all'][0]);
        $this->assertEquals($all[0], (string) $debug['groups.$id']['$all'][0]);
        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$in'][0]);
        $this->assertEquals($in[0], (string) $debug['groups.$id']['$in'][0]);
        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$ne']);
        $this->assertEquals($ne, (string) $debug['groups.$id']['$ne']);
        $this->assertInstanceOf('MongoId', $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($nin[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testPrepareNestedDocuments()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('address.subAddress.subAddress.subAddress.test')->equals('test');
        $query = $qb->getQuery();
        $debug = $query->debug('query');
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => 'test'), $debug);
    }

    public function testPreparePositionalOperator()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->field('phonenumbers.$.phonenumber')->equals('foo')
            ->field('phonenumbers.$')->set(array('phonenumber' => 'bar'));

        $this->assertEquals(array('phonenumbers.$.phonenumber' => 'foo'), $qb->getQueryArray());
        $this->assertEquals(array('$set' => array('phonenumbers.$' => array('phonenumber' => 'bar'))), $qb->getNewObj());
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
            ->field('address.subAddress.subAddress.subAddress.test')->notIn(array('test'));
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
        $dm = $this->createMock('Doctrine\\ODM\\MongoDB\\DocumentManager');
        $uw = $this->createMock('Doctrine\ODM\MongoDB\UnitOfWork');
        $documentPersister = $this->createMock('Doctrine\ODM\MongoDB\Persisters\DocumentPersister');
        $class = $this->createMock('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata');

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
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('targetDocument' => 'Foo', 'name' => 'foo')));

        $expr = new Expr($dm);
        $expr->setClassMetadata($class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses just $id if a targetDocument is set');
    }

    public function testReferencesUsesAllKeys()
    {
        $dm = $this->createMock('Doctrine\\ODM\\MongoDB\\DocumentManager');
        $uw = $this->createMock('Doctrine\ODM\MongoDB\UnitOfWork');
        $documentPersister = $this->createMock('Doctrine\ODM\MongoDB\Persisters\DocumentPersister');
        $class = $this->createMock('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata');

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
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('name' => 'foo')));

        $expr = new Expr($dm);
        $expr->setClassMetadata($class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses all keys if no targetDocument is set');
    }

    public function testReferencesUsesSomeKeys()
    {
        $dm = $this->createMock('Doctrine\\ODM\\MongoDB\\DocumentManager');
        $uw = $this->createMock('Doctrine\ODM\MongoDB\UnitOfWork');
        $documentPersister = $this->createMock('Doctrine\ODM\MongoDB\Persisters\DocumentPersister');
        $class = $this->createMock('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata');

        $expected = array('foo.$ref' => 'coll', 'foo.$id' => '1234');

        $dm->expects($this->once())
            ->method('createDBRef')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234')));
        $dm->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uw));
        $uw->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));
        $documentPersister->expects($this->once())
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));
        $class->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF, 'name' => 'foo')));

        $expr = new Expr($dm);
        $expr->setClassMetadata($class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses some keys if storeAs=dbRef is set');
    }
}
