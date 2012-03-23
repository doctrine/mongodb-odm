<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class RequireIndexesTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
    }

    public function testGetFieldsInQueryWithSimpleEquals()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->equals('test');
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryIgnoresWhereOperator()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->where('this.test > 0');
        $qb->addOr($qb->expr()->where('this.ok > 1'));
        $qb->addAnd($qb->expr()->field('username')->equals('jwage'));
        $query = $qb->getQuery();
        $this->assertEquals(array('username'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithElemMatch()
    {
        $date = new \DateTime();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('flashes')->elemMatch(
            $qb->expr()->field('startDate')->lt($date)->field('endDate')->gte($date)
        );
        $query = $qb->getQuery();
        $this->assertEquals(array(
            'flashes.startDate',
            'flashes.endDate'
        ), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithElemMatchAndOr()
    {
        $date = new \DateTime();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('flashes')->elemMatch(
            $qb->expr()->field('startDate')->lt($date)->field('endDate')->gte($date)->field('startDate')
                ->addOr($qb->expr()->field('something')->equals($date))

                ->where('this.id > 0')
        )->addAnd($qb->expr()->field('flashes.id')->equals('foo'));
        $query = $qb->getQuery();
        $this->assertEquals(array(
            'flashes.startDate',
            'flashes.endDate',
            'flashes.something',
            'flashes.id'
        ), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithOrAndIn()
    {
        $date = new \DateTime();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->addOr($qb->expr()->field('field1')->in(array(1)));
        $qb->addOr($qb->expr()->field('field2')->in(array(1)));
        $query = $qb->getQuery();
        $this->assertEquals(array('field1', 'field2'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithComplexQuery()
    {
        $date = new \DateTime();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->addOr($qb->expr()->field('field1')->in(array(1)));
        $qb->addAnd($qb->expr()->field('field2')->equals(1));
        $qb->field('field3')->elemMatch($qb->expr()->field('embedded')->range(1, 2));
        $qb->field('field4')->elemMatch($qb->expr()->addOr($qb->expr()->field('embedded')->equals($date)));
        $qb->field('field5')->equals('test');
        $query = $qb->getQuery();
        $this->assertEquals(array(
            'field1',
            'field2',
            'field3.embedded',
            'field4.embedded',
            'field5'
        ), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithIn()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->in(array(1));
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithNotIn()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->notIn(array(1));
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithNotEqual()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->notEqual(1);
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithNot()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->not(1);
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithReferences()
    {
        $reference = new DoesNotRequireIndexesDocument();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('reference')->references($reference);
        $qb->field('simpleReference')->references($reference);
        $query = $qb->getQuery();
        $this->assertEquals(array('reference.$id', 'simpleReference'), $query->getFieldsInQuery());
    }

    public function testGetFieldsInQueryWithIncludesReferences()
    {
        $reference = new DoesNotRequireIndexesDocument();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('reference')->includesReferenceTo($reference);
        $qb->field('simpleReference')->includesReferenceTo($reference);
        $query = $qb->getQuery();
        $this->assertEquals(array('reference.$id', 'simpleReference'), $query->getFieldsInQuery());
    }

    public function testIsIndexedTrue()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('indexed')->equals('test');
        $query = $qb->getQuery();
        $this->assertTrue($query->isIndexed());
    }

    public function testIsIndexedFalse()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('notIndexed')->equals('test');
        $query = $qb->getQuery();
        $this->assertFalse($query->isIndexed());

        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('indexed')->equals('test')
            ->field('notIndexed')->equals('test');
        $query = $qb->getQuery();
        $this->assertFalse($query->isIndexed());
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testRequireIndexesThrowsExceptionOnExecute()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('notIndexed')->equals('test');
        $query = $qb->getQuery();
        $query->execute();
    }

    public function testRequireIndexesExceptionMessage()
    {
        try {
            $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
                ->field('notIndexed')->equals('test');
            $query = $qb->getQuery();
            $query->execute();
        } catch (MongoDBException $e) {
            $this->assertEquals('Cannot execute unindexed queries on Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument. Unindexed fields: notIndexed', $e->getMessage());
        }
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testForceEnableRequireIndexes()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\DoesNotRequireIndexesDocument')
            ->field('notIndexed')->equals('test')
            ->requireIndexes();
        $query = $qb->getQuery();
        $query->execute();
    }

    public function testForceDisableRequireIndexes()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('notIndexed')->equals('test')
            ->requireIndexes(false);
        $query = $qb->getQuery();
        $query->execute();
    }


    public function testRequireIndexesFalse()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\DoesNotRequireIndexesDocument')
            ->field('notIndexed')->equals('test');
        $query = $qb->getQuery();
        $query->execute();
    }

    public function testRequireIndexesOnEmbeddedDocument()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('embedOne.indexed')->equals('test');
        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testRequireIndexesOnEmbeddedDocumentThrowsException()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('embedOne.notIndexed')->equals('test');
        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testRequireIndexesOnSortThrowException()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->sort('embedOne.notIndexed', 'asc');
        $query = $qb->getQuery();
        $query->execute();
    }

    public function testGetUnindexedFields()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument')
            ->field('embedOne.notIndexed')->equals('test')
            ->field('notIndexed')->equals('test')
            ->field('indexed')->equals('test');
        $query = $qb->getQuery();
        $this->assertEquals(array('embedOne.notIndexed', 'notIndexed'), $query->getUnindexedFields());
    }
}

/**
 * @ODM\Document(requireIndexes=true)
 */
class RequireIndexesDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\Index */
    public $indexed;

    /** @ODM\String */
    public $notIndexed;

    /** @ODM\EmbedOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesEmbeddedDocument") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesEmbeddedDocument") */
    public $embedMany;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\DoesNotRequireIndexesDocument") */
    public $reference;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\DoesNotRequireIndexesDocument", simple=true) */
    public $simpleReference;
}

/**
 * @ODM\Document(requireIndexes=false)
 */
class DoesNotRequireIndexesDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\Index */
    public $indexed;

    /** @ODM\String */
    public $notIndexed;
}

/** @ODM\EmbeddedDocument */
class RequireIndexesEmbeddedDocument
{
    /** @ODM\String @ODM\Index */
    public $indexed;

    /** @ODM\String */
    public $notIndexed;
}