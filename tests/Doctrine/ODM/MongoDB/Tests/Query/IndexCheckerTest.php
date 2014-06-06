<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\IndexChecker;

class IndexCheckerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Doctrine\ODM\MongoDB\Tests\Query\Doc');
    }
    
    /**
     * @dataProvider provideGetIndexesIncludingFields
     */
    public function testGetIndexesIncludingFields($fields, $expected)
    {
        $ic = $this->getDummyIndexChecker();
        $rm = new \ReflectionMethod(get_class($ic), 'getIndexesIncludingFields');
        $rm->setAccessible(true);
        $result = $rm->invoke($ic, $fields, false);
        if ($expected) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    
    public function provideGetIndexesIncludingFields()
    {
        return array(
            array(array('_id'),                 true),
            array(array('a'),                   true),
            array(array('b'),                   false),
            array(array('e'),                   false),
            array(array('b', 'a'),              true),
            array(array('a', 'c'),              true),
            array(array('a', 'e'),              false),
            array(array('b', 'e'),              false),
            array(array('a', 'b', 'c'),         true),
            array(array('d', 'c', 'a', 'b'),    true),
            array(array('d', 'c', 'b'),         false),
        );
    }
    
    /**
     * @dataProvider provideGetIndexCapableOfSorting
     */
    public function testGetIndexCapableOfSorting($indexes, $sort, $prefixPrepend, $expected)
    {
        $ic = $this->getDummyIndexChecker();
        $rm = new \ReflectionMethod(get_class($ic), 'getIndexCapableOfSorting');
        $rm->setAccessible(true);
        $result = $rm->invoke($ic, $indexes, $sort, $prefixPrepend);
        if ($expected) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    
    public function provideGetIndexCapableOfSorting()
    {
        $indexes = array(
            array('key' => array('a' => 1, 'b' => 1, 'c' => 1, 'd' => 1)),
        );
        return array(
            array($indexes, array('a' => 1), array(), true),
            array($indexes, array('a' => -1), array(), true),
            array($indexes, array('b' => 1), array(), false),
            array($indexes, array('b' => 1), array('a'), true),
            array($indexes, array('b' => -1), array('a'), true),
            array($indexes, array('e' => 1), array(), false),
            array($indexes, array('a' => 1, 'b' => 1), array(), true),
            array($indexes, array('a' => -1, 'b' => -1), array(), true),
            array($indexes, array('a' => 1, 'b' => -1), array(), false),
            array($indexes, array('a' => -1, 'b' => 1), array(), false),
            array($indexes, array('b' => 1, 'a' => 1), array(), false),
            array($indexes, array('a' => 1, 'c' => 1), array(), true),
            array($indexes, array('a' => 1, 'b' => 1, 'c' => 1, 'd' => 1), array(), true),
            array($indexes, array('b' => 1, 'c' => 1, 'd' => 1), array('a'), true),
            array($indexes, array('a' => 1), array('c'), true),
        );
    }
    
    private function getDummyIndexChecker()
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Query\Doc');
        $query = $qb->getQuery();
        $rc = new \ReflectionProperty(get_class($query), 'collection');
        $rc->setAccessible(true);
        return new IndexChecker($query, $rc->getValue($query));
    }
}

/**
 * @ODM\Document(indexes={ @ODM\Index(keys={"a"="asc", "b"="asc", "c"="asc", "d"="asc"}) })
 */
Class Doc
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Int @ODM\Index */
    public $a;
    
    /** @ODM\Int */
    public $b;
    
    /** @ODM\Int */
    public $c;
    
    /** @ODM\Int */
    public $d;
    
    /** @ODM\Int */
    public $e; // not indexed in Compound Index
}
