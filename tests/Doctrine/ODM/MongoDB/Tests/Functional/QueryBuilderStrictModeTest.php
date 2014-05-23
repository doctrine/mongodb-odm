<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

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
