<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\QueryLogger;
use Documents\Book;
use Documents\Chapter;
use Documents\IdentifiedChapter;
use Documents\Page;
use Documents\Phonebook;
use Documents\Phonenumber;

/**
 * CollectionPersister will throw exception when collection with atomicSet
 * or atomicSetArray should be handled by it. If no exception was thrown it
 * means that collection update was handled by DocumentPersister.
 */
class AtomicSetTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var QueryLogger
     */
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

    public function testAtomicInsertAndUpdate()
    {
        $user = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname = "Malarz";
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating a document and its embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertEquals('Malarz', $user->surname);
        $this->assertCount(2, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
        $this->assertEquals('87654321', $user->phonenumbers[1]->getPhonenumber());
    }

    public function testAtomicUpsert()
    {
        $user = new AtomicSetUser('Maciej');
        $user->id = new \MongoId();
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Upserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
    }

    /**
     * @dataProvider provideAtomicCollectionUnset
     */
    public function testAtomicCollectionUnset($clearWith)
    {
        $user = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname = "Malarz";
        $user->phonenumbers = $clearWith;
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating a document and unsetting its embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertEquals('Malarz', $user->surname);
        $this->assertCount(0, $user->phonenumbers);
    }

    public function provideAtomicCollectionUnset()
    {
        return array(
            array(null),
            array(array()),
            array(new ArrayCollection()),
        );
    }

    public function testAtomicCollectionClearAndUpdate()
    {
        $user = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $user->phonenumbers->clear();
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating emptied collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('87654321', $user->phonenumbers[0]->getPhonenumber());
    }

    public function testAtomicCollectionReplacedAndUpdated()
    {
        $user = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $user->phonenumbers = new ArrayCollection();
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating emptied collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('87654321', $user->phonenumbers[0]->getPhonenumber());
    }

    public function testAtomicSetArray()
    {
        $user = new AtomicSetUser('Maciej');
        $user->phonenumbersArray[1] = new Phonenumber('12345678');
        $user->phonenumbersArray[2] = new Phonenumber('87654321');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(2, $user->phonenumbersArray);
        $this->assertEquals('12345678', $user->phonenumbersArray[0]->getPhonenumber());
        $this->assertEquals('87654321', $user->phonenumbersArray[1]->getPhonenumber());

        unset($user->phonenumbersArray[0]);
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Unsetting an element within an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->phonenumbersArray);
        $this->assertEquals('87654321', $user->phonenumbersArray[0]->getPhonenumber());
        $this->assertFalse(isset($user->phonenumbersArray[1]));
    }

    public function testAtomicCollectionWithAnotherNested()
    {
        $user = new AtomicSetUser('Maciej');
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $user->phonebooks[] = $privateBook;
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with a nested embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(1, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->addPhonenumber(new Phonenumber('87654321'));
        $publicBook = new Phonebook('Public');
        $publicBook->addPhonenumber(new Phonenumber('10203040'));
        $user->phonebooks[] = $publicBook;
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating multiple, nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(2, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());
        $this->assertEquals('87654321', $privateBook->getPhonenumbers()->get(1)->getPhonenumber());
        $publicBook = $user->phonebooks[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->getPhonenumbers()->clear();
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Clearing a nested embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(0, $privateBook->getPhonenumbers());
        $publicBook = $user->phonebooks[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());
    }
    
    public function testWeNeedToGoDeeper()
    {
        $user = new AtomicSetUser('Maciej');
        $user->inception[0] = new AtomicSetInception('start');
        $user->inception[0]->one = new AtomicSetInception('start.one');
        $user->inception[0]->one->many[] = new AtomicSetInception('start.one.many.0');
        $user->inception[0]->one->many[] = new AtomicSetInception('start.one.many.1');
        $user->inception[0]->one->one = new AtomicSetInception('start.one.one');
        $user->inception[0]->one->one->many[] = new AtomicSetInception('start.one.one.many.0');
        $user->inception[0]->one->one->many[0]->many[] = new AtomicSetInception('start.one.one.many.0.many.0');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->inception);
        $this->assertEquals($user->inception[0]->value, 'start');
        $this->assertNotNull($user->inception[0]->one);
        $this->assertEquals($user->inception[0]->one->value, 'start.one');
        $this->assertCount(2, $user->inception[0]->one->many);
        $this->assertEquals($user->inception[0]->one->many[0]->value, 'start.one.many.0');
        $this->assertEquals($user->inception[0]->one->many[1]->value, 'start.one.many.1');
        $this->assertNotNull($user->inception[0]->one->one);
        $this->assertEquals($user->inception[0]->one->one->value, 'start.one.one');
        $this->assertCount(1, $user->inception[0]->one->one->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->value, 'start.one.one.many.0');
        $this->assertCount(1, $user->inception[0]->one->one->many[0]->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->many[0]->value, 'start.one.one.many.0.many.0');

        unset($user->inception[0]->one->many[0]);
        $user->inception[0]->one->many[] = new AtomicSetInception('start.one.many.2');
        $user->inception[0]->one->one->many[0]->many[] = new AtomicSetInception('start.one.one.many.0.many.1');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating nested collections on various levels requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->inception);
        $this->assertEquals($user->inception[0]->value, 'start');
        $this->assertNotNull($user->inception[0]->one);
        $this->assertEquals($user->inception[0]->one->value, 'start.one');
        $this->assertCount(2, $user->inception[0]->one->many);
        /* Note: Since the "start.one.many" collection uses a pushAll strategy,
         * "start.one.many.1" is reindexed at 0 after fetching. Before the last
         * flush (when we unset "start.one.many.0"), it would still have been
         * accessible via index 1.
         */
        $this->assertEquals($user->inception[0]->one->many[0]->value, 'start.one.many.1');
        $this->assertEquals($user->inception[0]->one->many[1]->value, 'start.one.many.2');
        $this->assertNotNull($user->inception[0]->one->one);
        $this->assertEquals($user->inception[0]->one->one->value, 'start.one.one');
        $this->assertCount(1, $user->inception[0]->one->one->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->value, 'start.one.one.many.0');
        $this->assertCount(2, $user->inception[0]->one->one->many[0]->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->many[0]->value, 'start.one.one.many.0.many.0');
        $this->assertEquals($user->inception[0]->one->one->many[0]->many[1]->value, 'start.one.one.many.0.many.1');
    }

    public function testUpdatingNestedCollectionWhileDeletingParent()
    {
        $user = new AtomicSetUser('Jon');
        $user->inception[0] = new AtomicSetInception('start');
        $user->inception[0]->many[0] = new AtomicSetInception('start.many.0');
        $user->inception[0]->many[0]->many[0] = new AtomicSetInception('start.many.0.many.0');
        $user->inception[0]->many[1] = new AtomicSetInception('start.many.1');
        $user->inception[0]->many[1]->many[0] = new AtomicSetInception('start.many.1.many.0');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document with nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->inception);
        $this->assertEquals($user->inception[0]->value, 'start');
        $this->assertCount(2, $user->inception[0]->many);
        $this->assertEquals($user->inception[0]->many[0]->value, 'start.many.0');
        $this->assertCount(1, $user->inception[0]->many[0]->many);
        $this->assertEquals($user->inception[0]->many[0]->many[0]->value, 'start.many.0.many.0');
        $this->assertEquals($user->inception[0]->many[1]->value, 'start.many.1');
        $this->assertCount(1, $user->inception[0]->many[0]->many);
        $this->assertEquals($user->inception[0]->many[1]->many[0]->value, 'start.many.1.many.0');

        $user->inception[0]->many[0]->many[0]->value = 'start.many.0.many.0-changed';
        $user->inception[0]->many[0]->many[1] = new AtomicSetInception('start.many.0.many.1');
        $user->inception[0]->many[0]->many->clear();
        $user->inception[0]->many[1]->many[1] = new AtomicSetInception('start.many.1.many.1');
        $user->inception[0]->many[1] = new AtomicSetInception('start.many.1');
        $user->inception[0]->many[1]->many[0] = new AtomicSetInception('start.many.1.many.0-new');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating nested collections while deleting parents requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->inception);
        $this->assertEquals($user->inception[0]->value, 'start');
        $this->assertCount(2, $user->inception[0]->many);
        $this->assertEquals($user->inception[0]->many[0]->value, 'start.many.0');
        $this->assertCount(0, $user->inception[0]->many[0]->many);
        $this->assertEquals($user->inception[0]->many[1]->value, 'start.many.1');
        $this->assertCount(1, $user->inception[0]->many[1]->many);
        $this->assertEquals($user->inception[0]->many[1]->many[0]->value, 'start.many.1.many.0-new');
    }

    public function testAtomicRefMany()
    {
        $malarzm = new AtomicSetUser('Maciej');
        $jmikola = new AtomicSetUser('Jeremy');
        $jonwage = new AtomicSetUser('Jon');

        $this->dm->persist($malarzm);
        $this->dm->persist($jmikola);
        $this->dm->persist($jonwage);
        $this->dm->flush();
        $this->ql->clear();

        $malarzm->friends[] = $jmikola;
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating empty atomic reference many requires one query');
        $this->dm->clear();

        $malarzm = $this->dm->find(get_class($malarzm), $malarzm->id);
        $this->assertCount(1, $malarzm->friends);
        $this->assertEquals('Jeremy', $malarzm->friends[0]->name);

        $jonwage = $this->dm->find(get_class($jonwage), $jonwage->id);
        $malarzm->friends[] = $jonwage;
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Updating existing atomic reference many requires one query');
        $this->dm->clear();

        $malarzm = $this->dm->find(get_class($malarzm), $malarzm->id);
        $this->assertCount(2, $malarzm->friends);
        $this->assertEquals('Jeremy', $malarzm->friends[0]->name);
        $this->assertEquals('Jon', $malarzm->friends[1]->name);

        $malarzm->friends->clear();
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Clearing atomic reference many requires one query');
        $this->dm->clear();
    }

    public function testAtomicSetUpdatesAllNestedCollectionsInOneQuery()
    {
        // Create a book which has one chapter with one page.
        $chapter1 = new Chapter();
        $chapter1->pages->add(new Page(1));
        $book = new Book('title');
        $book->chapters->add($chapter1);

        $this->dm->persist($book);

        // Saving this book should result in 1 query since we use strategy="atomicSet"
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a book with one chapter and page requires one query');

        // Simulate another PHP request which loads this record and tries to add an embedded document two levels deep...
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Now we add a new "page" to the only chapter in this book.
        $firstChapter = $book->chapters->first();
        $firstChapter->pages->add(new Page(2));

        // Updating this book should result in 1 query since we use strategy="atomicSet"
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Adding a page to the first chapter of the book requires one query');

        // this is failing if lifecycle callback postUpdate is recomputing change set
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals(2, $book->chapters->first()->pages->count(), "Two page objects are expected in the first chapter of the book.");
    }

    public function testReplacementOfEmbedManyElements()
    {
        // Create a book with a single chapter.
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        $firstChapter = $book->chapters->first();
        $firstChapter->name = "First chapter A";

        // Developers commonly attempt to replace the contents of an EmbedMany with a new ArrayCollection like this:
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new Chapter('Second chapter B'));
        $book->chapters = $replacementChapters;

        $this->dm->flush(); // <- Currently getting "Cannot update 'chapters' and 'chapters' at the same time" failures.

        // Simulate another PHP request.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Verify we see chapters A and B.
        $this->assertEquals('First chapter A', $book->chapters[0]->name);
        $this->assertEquals('Second chapter B', $book->chapters[1]->name);
    }

    public function testReplacementOfIdentifiedEmbedManyElements()
    {
        $book = new Book();
        $book->identifiedChapters->add(new IdentifiedChapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $firstChapter = $book->identifiedChapters->first();
        $firstChapter->name = "First chapter A";
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new IdentifiedChapter('Second chapter B'));
        $book->identifiedChapters = $replacementChapters;

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals('First chapter A', $book->identifiedChapters[0]->name);
        $this->assertEquals('Second chapter B', $book->identifiedChapters[1]->name);
    }

    public function testOnlyEmbeddedDocumentUpdated()
    {
        // Create a book with a single chapter.
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        // Modify the chapter's name.
        $book->chapters->first()->name = "First chapter A";

        $this->dm->flush();

        // Simulate another PHP request & verify the change was saved.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));

        $this->assertEquals('First chapter A', $book->chapters[0]->name, "The chapter title failed to update.");
    }

    public function testUpdatedEmbeddedDocumentAndDirtyCollectionInside()
    {
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $firstChapter = $book->chapters->first();
        $firstChapter->name = "Apple";

        // Add some pages.
        $firstChapter->pages->add(new Page(1));
        $firstChapter->pages->add(new Page(2));

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(array('_id' => $book->id));
        $this->assertEquals(2, $book->chapters->first()->pages->count());
    }
}

/**
 * @ODM\Document
 */
class AtomicSetUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="int") @ODM\Version */
    public $version = 1;

    /** @ODM\Field(type="string") */
    public $surname;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonenumber") */
    public $phonenumbers;

    /** @ODM\EmbedMany(strategy="atomicSetArray", targetDocument="Documents\Phonenumber") */
    public $phonenumbersArray;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonebook") */
    public $phonebooks;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="AtomicSetInception") */
    public $inception;

    /** @ODM\ReferenceMany(strategy="atomicSetArray", targetDocument="AtomicSetUser") */
    public $friends;

    public function __construct($name)
    {
        $this->name = $name;
        $this->phonenumbers = new ArrayCollection();
        $this->phonenumbersArray = new ArrayCollection();
        $this->phonebooks = new ArrayCollection();
        $this->friends = new ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class AtomicSetInception
{
    /** @ODM\Field(type="string") */
    public $value;

    /** @ODM\EmbedOne(targetDocument="AtomicSetInception") */
    public $one;

    /** @ODM\EmbedMany(targetDocument="AtomicSetInception") */
    public $many;

    public function __construct($value)
    {
        $this->value = $value;
        $this->many = new ArrayCollection();
    }
}
