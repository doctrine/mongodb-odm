<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\QueryLogger;

class GH1126Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $ql;

    protected function getConfiguration()
    {
        if ( ! isset($this->ql)) {
            $this->ql = new QueryLogger();
        }

        $config = parent::getConfiguration();
        $config->setLoggerCallable($this->ql);

        return $config;
    }

    public function testAtomicSetStrategyVersusLifecycleCallbacks()
    {
        // Create a book which has one chapter with one page.
        $chapter1 = new GH1126Chapter();
        $chapter1->addPage(new GH1126Page(1));
        $book = new GH1126Book('title');
        $book->addChapter($chapter1);

        $this->dm->persist($book);

        // Saving this book should result in 1 query since we use strategy="atomicSet"
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a book with one chapter and page requires one query');

        // Simulate another PHP request which loads this record and tries to add an embedded document two levels deep...
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1126Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Now we add a new "page" to the only chapter in this book.
        $firstChapter = $book->chapters->first();
        $firstChapter->addPage(new GH1126Page(2));

        // Updating this book should result in 1 query since we use strategy="atomicSet"
        $this->ql->clear();
        $this->dm->flush();
        $this->dm->clear();

        $this->assertCount(1, $this->ql, 'Adding a new page embedded document should be accomplished with one statement since we are using atomicSet.');

        $book = $this->dm->getRepository(GH1126Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals(2, $book->chapters->first()->pages->count(), "Two page objects are expected in the first chapter of the book.");
    }
}

/** @ODM\Document */
class GH1126Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Int @ODM\Version */
    public $version = 1;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="GH1126Chapter", strategy="atomicSet") */
    public $chapters;

    public function __construct($name)
    {
        $this->name = $name;
        $this->chapters = new ArrayCollection();
    }

    public function addChapter(GH1126Chapter $chapter)
    {
        $this->chapters->add($chapter);
    }
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\HasLifecycleCallbacks
 */
class GH1126Chapter
{
    /** @ODM\EmbedMany(targetDocument="GH1126Page") */
    public $pages;

    /** @ODM\Int */
    public $nbPages = 0;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function addPage(GH1126Page $page)
    {
        $this->pages->add($page);
    }

    /**
     * @ODM\PostUpdate
     */
    public function doThisAfterAnUpdate()
    {
        /* Do not do this at home, it is here only to see if nothing breaks, 
         * field will not be updated in database with new value unless another
         * flush() is made.
         */
        $this->nbPages = $this->pages->count();
    }
}

/** @ODM\EmbeddedDocument */
class GH1126Page
{
    /** @ODM\Int */
    public $pageNumber;

    public function __construct($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }
}
