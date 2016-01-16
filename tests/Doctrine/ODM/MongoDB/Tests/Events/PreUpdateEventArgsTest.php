<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Documents\Article;
use Documents\Book;
use Documents\Chapter;
use Documents\Page;

class PreUpdateEventArgsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testChangeSetIsUpdated()
    {
        $this->dm->getEventManager()->addEventListener(Events::preUpdate, new ChangeSetIsUpdatedListener());

        $a = new Article();
        $a->setTitle('Title');
        $this->dm->persist($a);
        $this->dm->flush();
        $a->setBody('Body');
        $this->dm->flush();
        $this->dm->clear();
        $a = $this->dm->find(Article::class, $a->getId());
        $this->assertEquals('Changed', $a->getBody());
    }

    public function testCollectionsAreInChangeSet()
    {
        $listener = new CollectionsAreInChangeSetListener($this);
        $this->dm->getEventManager()->addEventListener(Events::preUpdate, $listener);

        $book = new Book();
        $book->chapters->add($chapter = new Chapter('A'));
        $chapter->pages->add(new Page(1));
        $chapter->pages->add(new Page(2));

        $this->dm->persist($book);
        $this->dm->flush();

        $book->chapters->add($chapter2 = new Chapter('B'));
        $chapter->pages->add(new Page(3));
        $this->dm->flush();

        $listener->checkOnly([ Chapter::class ]);
        unset($chapter->pages[0]);
        $this->dm->flush();

        $listener->checkOnly([ Book::class ]);

        $book->chapters->removeElement($chapter2);
        $this->dm->flush();

        $book->chapters->clear();
        $this->dm->flush();
    }
}

class ChangeSetIsUpdatedListener
{
    public function preUpdate(PreUpdateEventArgs $e)
    {
        $e->setNewValue('body', 'Changed');
    }
}

class CollectionsAreInChangeSetListener
{
    private $allowed;

    private $phpunit;

    public function __construct(PreUpdateEventArgsTest $phpunit)
    {
        $this->allowed = [ Book::class, Chapter::class ];
        $this->phpunit = $phpunit;
    }

    public function checkOnly(array $allowed)
    {
        $this->allowed = $allowed;
    }

    public function preUpdate(PreUpdateEventArgs $e)
    {
        switch (get_class($e->getDocument())) {
            case Book::class:
                if (in_array(Book::class, $this->allowed)) {
                    $this->phpunit->assertTrue($e->hasChangedField('chapters'));
                }
                break;
            case Chapter::class:
                if (in_array(Chapter::class, $this->allowed)) {
                    $this->phpunit->assertTrue($e->hasChangedField('pages'));
                }
                break;
        }
    }
}
