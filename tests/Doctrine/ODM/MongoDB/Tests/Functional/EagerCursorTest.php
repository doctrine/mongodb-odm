<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class EagerCursorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $document;
    private $test;

    public function setUp()
    {
        parent::setUp();

        $document = array('test' => 'test');
        $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\EagerTestDocument')->insert($document);

        $this->document = new EagerTestDocument();
        $this->document->id = (string) $document['_id'];
        $this->document->test = 'test';

        $qb = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\EagerTestDocument');
        $qb->eagerCursor(true);
        $this->test = $qb->getQuery()->execute();
    }

    public function testEagerCursor()
    {
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\EagerCursor', $this->test);
    }

    public function testIsInitialized()
    {
        $this->assertFalse($this->test->isInitialized());
        $this->test->initialize();
        $this->assertTrue($this->test->isInitialized());
    }

    public function testCount()
    {
        $this->assertEquals(1, count($this->test));
    }

    public function testGetSingleResult()
    {
        $this->assertEquals($this->document, $this->test->getSingleResult());
    }

    public function testToArray()
    {
        $this->assertEquals(array($this->document->id => $this->document), $this->test->toArray());
    }

    public function testHydrate()
    {
        $this->test->hydrate(false);
        $this->assertTrue(is_array($this->test->getSingleResult()));

        $this->test->hydrate(true);
        $this->assertTrue(is_object($this->test->getSingleResult()));
    }

    public function testRewind()
    {
        $this->test->toArray();
        $this->assertFalse($this->test->next());
        $this->test->rewind();
        $this->assertEquals($this->document, $this->test->current());
        $this->assertFalse($this->test->next());
    }
}

/** @ODM\Document */
class EagerTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $test;
}