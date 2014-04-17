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
    public function testGetIndexesIncludingFields($fields, $allowLessEfficientIndexes, $expected)
    {
        $ic = $this->getDummyIndexChecker($allowLessEfficientIndexes);
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
            array(array('_id'),                 true,   true),
            array(array('a'),                   true,   true),
            array(array('b'),                   true,   false),
            array(array('e'),                   true,   false),
            array(array('b', 'a'),              true,   true),
            array(array('a', 'c'),              true,   true),
            array(array('a', 'c'),              false,  false),
            array(array('a', 'e'),              true,   false),
            array(array('b', 'e'),              true,   false),
            array(array('a', 'b', 'c'),         true,   true),
            array(array('d', 'c', 'a', 'b'),    true,   true),
            array(array('d', 'c', 'b'),         true,   false),
        );
    }
    
    /**
     * @dataProvider provideGetIndexCapableOfSorting
     */
    public function testGetIndexCapableOfSorting($indexes, $sort, $prefixPrepend, $allowLessEfficientIndexes, $expected)
    {
        $ic = $this->getDummyIndexChecker($allowLessEfficientIndexes);
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
            array($indexes, array('a' => 1), array(), true, true),
            array($indexes, array('a' => -1), array(), true, true),
            array($indexes, array('b' => 1), array(), true, false),
            array($indexes, array('b' => 1), array('a'), true, true),
            array($indexes, array('b' => -1), array('a'), true, true),
            array($indexes, array('e' => 1), array(), true, false),
            array($indexes, array('a' => 1, 'b' => 1), array(), true, true),
            array($indexes, array('a' => -1, 'b' => -1), array(), true, true),
            array($indexes, array('a' => 1, 'b' => -1), array(), true, false),
            array($indexes, array('a' => -1, 'b' => 1), array(), true, false),
            array($indexes, array('b' => 1, 'a' => 1), array(), true, false),
            array($indexes, array('a' => 1, 'c' => 1), array(), true, true),
            array($indexes, array('a' => 1, 'c' => 1), array(), false, false),
            array($indexes, array('a' => 1, 'c' => 1), array('b'), false, true),
            array($indexes, array('a' => 1, 'b' => 1, 'c' => 1, 'd' => 1), array(), true, true),
            array($indexes, array('b' => 1, 'c' => 1, 'd' => 1), array('a'), true, true),
            array($indexes, array('a' => 1), array('c'), true, true),
            array($indexes, array('a' => 1), array('c'), false, true),
        );
    }
    
    private function getDummyIndexChecker($allowLessEfficientIndexes)
    {
        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Query\Doc');
        $query = $qb->getQuery();
        $rc = new \ReflectionProperty(get_class($query), 'collection');
        $rc->setAccessible(true);
        return new IndexChecker($query, $rc->getValue($query), $allowLessEfficientIndexes);
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
