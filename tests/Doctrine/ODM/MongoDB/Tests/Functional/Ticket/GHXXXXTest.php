<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GHXXXXTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReplacementOfIdentifiedEmbedManyElements()
    {
        $book = new GHXXXXBook();
        $book->chapters->add(new GHXXXXChapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GHXXXXBook::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $firstChapter = $book->chapters->first();
        $firstChapter->name = "Apple";

        // Add some pages.
        $firstChapter->pages->add(new GHXXXXPage(1));
        $firstChapter->pages->add(new GHXXXXPage(2));

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GHXXXXBook::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals(2, $book->chapters->first()->pages->count());
    }
}

/** @ODM\Document */
class GHXXXXBook
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GHXXXXChapter", strategy="atomicSet") */
    public $chapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GHXXXXChapter
{
    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="GHXXXXPage") */
    public $pages;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class GHXXXXPage
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
