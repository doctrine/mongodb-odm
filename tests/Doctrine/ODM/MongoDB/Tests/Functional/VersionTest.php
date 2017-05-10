<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

class VersionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testVersioningWhenManipulatingEmbedMany()
    {
        $expectedVersion = 1;
        $doc = new VersionedDocument();
        $doc->name = 'test';
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 1');
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 2');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 3');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[0]->embedMany[] = new VersionedEmbeddedDocument('deeply embed 1');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        unset($doc->embedMany[1]);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany->clear();
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany = null;
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
    }

    public function testAtomicSetWorksAsExpected()
    {
        global $queries_detected;

        $dm = $this->dm;

        // Create a book which has one chapter with one page.
        $chapter1 = new Chapter();
        $chapter1->addPage(new Page(1));
        $book = new Book('HARRY POTTER');
        $book->addChapter($chapter1);

        $dm->persist($book);

        // Saving this book should result in 1 query since we use strategy="atomicFlush"
        $queries_detected = [];
        $dm->flush();
        $this->assertEquals(1, count($queries_detected));

        $id = $book->id;

        // Simulate another PHP request which loads this record and tries to add an embedded document two levels deep...
        $dm->clear();
        $book = $dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\Book')->findOneBy(array('_id' => $id));

        // Now we add a new "page" to the only chapter in this book.
        $firstChapter = $book->getChapters()->first();
        $firstChapter->addPage(new Page(2));

        // Updating this book should result in 1 query since we use strategy="atomicFlush"
        $queries_detected = [];
        $dm->flush();
        $num_queries = count($queries_detected);

        $this->assertEquals(1, $num_queries, "Despite use of atomicSet, $num_queries queries were detected when flushing this change. We expected only one query. This will result in data loss and corruption.");
    }

    protected function getConfiguration()
    {
        $config = parent::getConfiguration();
        $config->setLoggerCallable(array($this, 'notifyQueryTriggered'));
        return $config;
    }

    public function notifyQueryTriggered($event)
    {
        global $queries_detected;
        if ($queries_detected == null) {
            $queries_detected = [];
        }
        $queries_detected[] = $event;
    }
}

/**
 * @ODM\Document
 */
class VersionedDocument
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Field(type="int") @ODM\Version */
    public $version = 1;
    
    /** @ODM\Field(type="string") */
    public $name;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany = array();
    
    public function __construct()
    {
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class VersionedEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $value;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany;
    
    public function __construct($value) 
    {
        $this->value = $value;
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

// --

/**
 * @ODM\Document
 */
class Book
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Int @ODM\Version */
    public $version = 1;

    /** @ODM\String */
    public $name;

    /**
     * @ODM\EmbedMany(
     *  targetDocument="Chapter",
     *  strategy="atomicSet"
     * )
     */
    public $chapters;

    public function __construct($name)
    {
        $this->name = $name;
        $this->chapters = new ArrayCollection();
    }

    public function addChapter(Chapter $chapter)
    {
        $this->chapters->add($chapter);
    }

    public function getChapters()
    {
        return $this->chapters;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Chapter
{
    /**
     * @ODM\EmbedMany(
     *  targetDocument="Chapter"
     * )
     */
    public $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function addPage(Page $page)
    {
        $this->pages->add($page);
    }

    public function getPages()
    {
        return $this->pages;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Page
{
    /**
     * @ODM\Int
     */
    public $page_number;

    public function __construct($page_number)
    {
        $this->page_number = $page_number;
    }
}
