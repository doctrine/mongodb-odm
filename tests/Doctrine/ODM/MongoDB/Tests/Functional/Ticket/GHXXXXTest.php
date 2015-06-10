<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\QueryLogger;

class GHXXXXTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReplacementOfEmbedManyElements()
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

        // Developers commonly attempt to replace the contents of an EmbedMany with a new ArrayCollection like this:
        $replacementChatpers = new ArrayCollection();
        $replacementChatpers->add($book->chapters->first());
        $replacementChatpers->add(new GHXXXXChapter('B'));
        $book->chapters = $replacementChatpers;

        $this->dm->flush(); // <- Currently getting "Cannot update 'chapters' and 'chapters' at the same time" failures.

        // Simulate another PHP request.
        $this->dm->clear();
        $book = $this->dm->getRepository(GHXXXXBook::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Verify we see chapters A and B.
        $discoveredChapterTitles = array();
        foreach ($book->chapters as $thisChapter) {
            $discoveredChapterTitles[] = $thisChapter->name;
        }
        $this->assertTrue(in_array('A', $discoveredChapterTitles));
        $this->assertTrue(in_array('B', $discoveredChapterTitles));
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
        $this->pages = new ArrayCollection();
        $this->name = $name;
    }
}
