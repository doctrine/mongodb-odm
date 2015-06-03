<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
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

    public function testNoDataLossOccursWhenAddingToPersistentCollection()
    {
        $dm = $this->dm;

        // Create a book with one chapter.
        $book = new Book('HARRY POTTER');
        $book->addChapter(new Chapter('Chapter One'));

        $dm->persist($book);
        $dm->flush();

        $id = $book->id;
        unset($book);

        // Do a fresh load of the document.
        $dm->clear();
        $book = $dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\Book')->findOneBy(array('_id' => $id));

        // Add a second chapter to the book.
        $book->addChapter(new Chapter('Chapter Two'));

        $dm->flush();

        // Do a fresh load of the document.
        $dm->clear();
        $book = $dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\Book')->findOneBy(array('_id' => $id));

        // Make sure both chapters are found.
        $this->assertEquals(2, $book->getChapters()->count());
    }
}

/**
 * @ODM\Document
 */
class VersionedDocument
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Int @ODM\Version */
    public $version = 1;
    
    /** @ODM\String */
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
    /** @ODM\String */
    public $value;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany;
    
    public function __construct($value) 
    {
        $this->value = $value;
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

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
     *  targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Chapter",
     *  strategy="set"
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
     * @ODM\String
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}