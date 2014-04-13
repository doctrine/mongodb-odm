<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class CountableDocumentRepositoryTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCountableDocumentRepository()
    {
        $r = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\CountableRepositoryDocument');
        $this->assertTrue($r instanceof \Countable);
        $d1 = $this->createAndPersistObject("Test 1");
        $d2 = $this->createAndPersistObject("Test 2");
        $d3 = $this->createAndPersistObject("Test 3");
        $this->assertEquals(0, count($r));
        $this->dm->flush();
        $this->assertEquals(3, count($r));
        $this->dm->remove($d2);
        $this->dm->flush();
        $this->assertEquals(2, count($r));
    }
    
    public function testCountableDocumentRepositoryWithFilter()
    {
        $r = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\CountableRepositoryDocument');
        $d1 = $this->createAndPersistObject("Test 1");
        $d2 = $this->createAndPersistObject("Test 2", 1);
        $d3 = $this->createAndPersistObject("Test 3", 1);
        $this->dm->flush();
        $this->fc = $this->dm->getFilterCollection();
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Doctrine\ODM\MongoDB\Tests\Functional\CountableRepositoryDocument');
        $testFilter->setParameter('field', 'forFilter');
        $testFilter->setParameter('value', 1);
        $this->assertEquals(2, count($r));
        $this->fc->disable('testFilter');
        $this->assertEquals(3, count($r));
    }
    
    private function createAndPersistObject($text, $forFilter=null)
    {
        $d = new CountableRepositoryDocument();
        $d->text=$text;
        $d->forFilter=$forFilter;
        $this->dm->persist($d);
        return $d;
    }
}

/**
 * @ODM\Document
 */
class CountableRepositoryDocument
{
    /** @ODM\Id(strategy="auto") */
    private $id;
    
    /** @ODM\String */
    public $text;
    
    /** @ODM\Int */
    public $forFilter;
}
