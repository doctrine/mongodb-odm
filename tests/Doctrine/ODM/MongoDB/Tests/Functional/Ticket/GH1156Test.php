<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1156Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReplacementOfIdentifiedEmbedManyElements()
    {
        $book = new GH1156Book();
        $book->chapters->add(new GH1156Chapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GH1156Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $firstChapter = $book->chapters->first();
        $firstChapter->name = "Apple";

        // Add some pages.
        $firstChapter->pages->add(new GH1156Page(1));
        $firstChapter->pages->add(new GH1156Page(2));

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GH1156Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals(2, $book->chapters->first()->pages->count());
    }
}

/** @ODM\Document */
class GH1156Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH1156Chapter", strategy="atomicSet") */
    public $chapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1156Chapter
{
    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="GH1156Page") */
    public $pages;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class GH1156Page
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Integer */
    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }
}
