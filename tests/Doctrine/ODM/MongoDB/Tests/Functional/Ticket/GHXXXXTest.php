<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GHXXXXTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFailureToUpdateEmbeddedDocumentWithAtomicSet()
    {
        // Create a book with a single chapter.
        $book = new GHXXXXBook();
        $book->chapters->add(new GHXXXXChapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(GHXXXXBook::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Modify the chapter's name.
        $book->chapters->first()->name = "First chapter A";

        $this->dm->flush();

        // Simulate another PHP request & verify the change was saved.
        $this->dm->clear();
        $book = $this->dm->getRepository(GHXXXXBook::CLASSNAME)->findOneBy(array('_id' => $book->id));

        $this->assertEquals('First chapter A', $book->chapters[0]->name, "The chapter title failed to update.");
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

    public function __construct($name)
    {
        $this->name = $name;
    }
}
