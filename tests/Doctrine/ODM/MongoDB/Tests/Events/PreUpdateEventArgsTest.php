<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Article;
use Documents\Book;
use Documents\Chapter;
use Documents\Page;
use PHPUnit\Framework\Assert;

use function get_class;
use function in_array;

class PreUpdateEventArgsTest extends BaseTest
{
    public function testChangeSetIsUpdated(): void
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
        self::assertEquals('Changed', $a->getBody());
    }

    public function testCollectionsAreInChangeSet(): void
    {
        $listener = new CollectionsAreInChangeSetListener();
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

        $listener->checkOnly([Chapter::class]);
        unset($chapter->pages[0]);
        $this->dm->flush();

        $listener->checkOnly([Book::class]);

        $book->chapters->removeElement($chapter2);
        $this->dm->flush();

        $book->chapters->clear();
        $this->dm->flush();
    }
}

class ChangeSetIsUpdatedListener
{
    public function preUpdate(PreUpdateEventArgs $e): void
    {
        $e->setNewValue('body', 'Changed');
    }
}

class CollectionsAreInChangeSetListener
{
    /** @var list<class-string> */
    private array $allowed = [Book::class, Chapter::class];

    /** @param list<class-string> $allowed */
    public function checkOnly(array $allowed): void
    {
        $this->allowed = $allowed;
    }

    public function preUpdate(PreUpdateEventArgs $e): void
    {
        switch (get_class($e->getDocument())) {
            case Book::class:
                if (in_array(Book::class, $this->allowed, true)) {
                    Assert::assertTrue($e->hasChangedField('chapters'));
                }

                break;
            case Chapter::class:
                if (in_array(Chapter::class, $this->allowed, true)) {
                    Assert::assertTrue($e->hasChangedField('pages'));
                }

                break;
        }
    }
}
