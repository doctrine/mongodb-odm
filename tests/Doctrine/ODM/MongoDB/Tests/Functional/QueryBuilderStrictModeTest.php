<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;

class QueryBuilderStrictModeTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimpleFieldsPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('count')->notEqual(3)
                ->sort('createdAt', 'asc');
        $this->assertTrue($qb->getQuery() instanceof Query);
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field cont in Documents\User
     */
    public function testSimpleFieldsNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('cont')->notEqual(3)
                ->getQuery();
    }
    
    public function testSimpleEmbedOnePassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('address.city')->equals('Cracow');
        $this->assertTrue($qb->getQuery() instanceof Query);
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field adress in Documents\User
     */
    public function testSimpleEmbedOneNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('adress.city')->equals('Cracow')
                ->getQuery();
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field ciity in Documents\Address
     */
    public function testSimpleEmbedOnePropertyNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('address.ciity')->equals('Cracow')
                ->getQuery();
    }
    
    public function testDeepEmbedPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('address.subAddress.city')->equals('Cracow');
        $this->assertTrue($qb->getQuery() instanceof Query);
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field ciity in Documents\Address
     */
    public function testDeepEmbedNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('address.subAddress.ciity')->equals('Cracow')
                ->getQuery();
    }
    
    public function testReferenceOnePassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('account.name')->equals('Maciej');
        $this->assertTrue($qb->getQuery() instanceof Query);
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field namme in Documents\Account
     */
    public function testReferenceOneNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('account.namme')->equals('Maciej')
                ->getQuery();
    }
    
    public function testDifferentNameAndFieldNamePassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('sortedAscGroups.name')->equals('Supergroup');
        $this->assertTrue($qb->getQuery() instanceof Query);
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field sortedAscGroupss in Documents\User
     */
    public function testDifferentNameAndFieldNameNotPassing()
    {
        $qb = $this->qb('Documents\User')
                ->field('sortedAscGroupss.name')->equals('Supergroup')
                ->getQuery();
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field ciity in Documents\Address. Have you meant city?
     */
    public function testSuggestingName()
    {
        $qb = $this->qb('Documents\User')
                ->field('address.ciity')->equals('Cracow')
                ->getQuery();
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Unknown field bat in Doctrine\ODM\MongoDB\Tests\Functional\DocumentWithSimilarFields. Have you meant one of bar, baz?
     */
    public function testSuggestingNames()
    {
        $qb = $this->qb(__NAMESPACE__ . '\DocumentWithSimilarFields')
                ->field('bat')->equals('foo')
                ->getQuery();
    }
    
    /**
     * @param string $class
     * @return Builder
     */
    private function qb($class)
    {
        return $this->dm->createQueryBuilder($class)
                ->strictMode();
    }
}

/** @ODM\Document */
class DocumentWithSimilarFields
{
    /** @ODM\Id */
    private $id;
    
    /** @ODM\Field */
    private $bar;
    
    /** @ODM\Field */
    private $baz;
}
