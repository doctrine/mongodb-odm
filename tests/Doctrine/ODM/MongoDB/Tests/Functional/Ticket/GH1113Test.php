<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\QueryLogger;

class GH1113Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

    public function testAtomicSetUpdatesAllNestedCollectionsInOneQuery()
    {
        // Create a book which has one chapter with one page.
        $chapter1 = new GH1113Chapter();
        $chapter1->addPage(new GH1113Page(1));
        $book = new GH1113Book('title');
        $book->addChapter($chapter1);

        $this->dm->persist($book);

        // Saving this book should result in 1 query since we use strategy="atomicSet"
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a book with one chapter and page requires one query');

        // Simulate another PHP request which loads this record and tries to add an embedded document two levels deep...
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1113Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Now we add a new "page" to the only chapter in this book.
        $firstChapter = $book->chapters->first();
        $firstChapter->addPage(new GH1113Page(2));

        // Updating this book should result in 1 query since we use strategy="atomicSet"
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Adding a page to the first chapter of the book requires one query');
    }
}

/** @ODM\Document */
class GH1113Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Int @ODM\Version */
    public $version = 1;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="GH1113Chapter", strategy="atomicSet") */
    public $chapters;

    public function __construct($name)
    {
        $this->name = $name;
        $this->chapters = new ArrayCollection();
    }

    public function addChapter(GH1113Chapter $chapter)
    {
        $this->chapters->add($chapter);
    }
}

/** @ODM\EmbeddedDocument */
class GH1113Chapter
{
    /** @ODM\EmbedMany(targetDocument="GH1113Page") */
    public $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function addPage(GH1113Page $page)
    {
        $this->pages->add($page);
    }
}

/** @ODM\EmbeddedDocument */
class GH1113Page
{
    /** @ODM\Int */
    public $pageNumber;

    public function __construct($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }
}
