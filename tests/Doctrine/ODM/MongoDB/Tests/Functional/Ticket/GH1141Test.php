<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1141Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReplacementOfEmbedManyElements()
    {
        // Create a book with a single chapter.
        $book = new GH1141Book();
        $book->chapters->add(new GH1141Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1141Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        $firstChapter = $book->chapters->first();
        $firstChapter->name = "First chapter A";

        // Developers commonly attempt to replace the contents of an EmbedMany with a new ArrayCollection like this:
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new GH1141Chapter('Second chapter B'));
        $book->chapters = $replacementChapters;

        $this->dm->flush(); // <- Currently getting "Cannot update 'chapters' and 'chapters' at the same time" failures.

        // Simulate another PHP request.
        $this->dm->clear();
        $book = $this->dm->getRepository(GH1141Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Verify we see chapters A and B.
        $this->assertEquals('First chapter A', $book->chapters[0]->name);
        $this->assertEquals('Second chapter B', $book->chapters[1]->name);
    }

    public function testReplacementOfIdentifiedEmbedManyElements()
    {
        $book = new GH1141Book();
        $book->identifiedChapters->add(new GH1141IdentifiedChapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GH1141Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $firstChapter = $book->identifiedChapters->first();
        $firstChapter->name = "First chapter A";
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new GH1141IdentifiedChapter('Second chapter B'));
        $book->identifiedChapters = $replacementChapters;

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(GH1141Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals('First chapter A', $book->identifiedChapters[0]->name);
        $this->assertEquals('Second chapter B', $book->identifiedChapters[1]->name);
    }
}

/** @ODM\Document */
class GH1141Book
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH1141Chapter", strategy="atomicSet") */
    public $chapters;

    /** @ODM\EmbedMany(targetDocument="GH1141IdentifiedChapter", strategy="atomicSet") */
    public $identifiedChapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
        $this->identifiedChapters = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1141Chapter
{
    /** @ODM\String */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class GH1141IdentifiedChapter
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
