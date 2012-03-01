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

    public function testGetFieldsInQuery()
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

        $date = new \DateTime();
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\RequireIndexesDocument');
        $qb->field('test')->in(array(1));
        $query = $qb->getQuery();
        $this->assertEquals(array('test'), $query->getFieldsInQuery());

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