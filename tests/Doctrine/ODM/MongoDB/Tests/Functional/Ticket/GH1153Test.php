<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1153Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFailureToUpdateEmbeddedDocumentWithAtomicSet()
    {
        // Create a book with a single chapter.
        $book = new GH1153Book();
        $book->chapters->add(new GH1153Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1153Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Modify the chapter's name.
        $book->chapters->first()->name = "First chapter A";

        $this->dm->flush();

        // Simulate another PHP request & verify the change was saved.
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1153Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        $this->assertEquals('First chapter A', $book->chapters[0]->name, "The chapter title failed to update.");
    }
}

/** @ODM\Document */
class GH1153Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH1153Chapter", strategy="atomicSet") */
    public $chapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1153Chapter
{
    /** @ODM\String */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
