<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Book;
use Documents\Chapter;
use Documents\IdentifiedChapter;
use Documents\Page;
use Documents\Phonebook;
use Documents\Phonenumber;
use MongoDB\BSON\ObjectId;

use function get_class;

/**
 * CollectionPersister will throw exception when collection with atomicSet
 * or atomicSetArray should be handled by it. If no exception was thrown it
 * means that collection update was handled by DocumentPersister.
 */
class AtomicSetTest extends BaseTest
{
    private CommandLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown(): void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testAtomicInsertAndUpdate(): void
    {
        $user                 = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonenumbers);
        self::assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname        = 'Malarz';
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating a document and its embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertEquals('Malarz', $user->surname);
        self::assertCount(2, $user->phonenumbers);
        self::assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
        self::assertEquals('87654321', $user->phonenumbers[1]->getPhonenumber());
    }

    public function testAtomicUpsert(): void
    {
        $user                 = new AtomicSetUser('Maciej');
        $user->id             = new ObjectId();
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Upserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonenumbers);
        self::assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
    }

    /**
     * @param mixed[]|ArrayCollection<int, mixed>|null $clearWith
     *
     * @dataProvider provideAtomicCollectionUnset
     */
    public function testAtomicCollectionUnset($clearWith): void
    {
        $user                 = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonenumbers);
        self::assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname      = 'Malarz';
        $user->phonenumbers = $clearWith;
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating a document and unsetting its embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertEquals('Malarz', $user->surname);
        self::assertEmpty($user->phonenumbers);
    }

    public function provideAtomicCollectionUnset(): array
    {
        return [
            [null],
            [[]],
            [new ArrayCollection()],
        ];
    }

    public function testAtomicCollectionClearAndUpdate(): void
    {
        $user                 = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $user->phonenumbers->clear();
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating emptied collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonenumbers);
        self::assertEquals('87654321', $user->phonenumbers[0]->getPhonenumber());
    }

    public function testAtomicCollectionReplacedAndUpdated(): void
    {
        $user                 = new AtomicSetUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user               = $this->dm->getRepository(get_class($user))->find($user->id);
        $user->phonenumbers = new ArrayCollection([new Phonenumber('87654321')]);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating emptied collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonenumbers);
        self::assertEquals('87654321', $user->phonenumbers[0]->getPhonenumber());
    }

    public function testAtomicSetArray(): void
    {
        $user                       = new AtomicSetUser('Maciej');
        $user->phonenumbersArray[1] = new Phonenumber('12345678');
        $user->phonenumbersArray[2] = new Phonenumber('87654321');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(2, $user->phonenumbersArray);
        self::assertEquals('12345678', $user->phonenumbersArray[0]->getPhonenumber());
        self::assertEquals('87654321', $user->phonenumbersArray[1]->getPhonenumber());

        unset($user->phonenumbersArray[0]);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Unsetting an element within an embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(1, $user->phonenumbersArray);
        self::assertEquals('87654321', $user->phonenumbersArray[0]->getPhonenumber());
        self::assertFalse(isset($user->phonenumbersArray[1]));
    }

    public function testAtomicCollectionWithAnotherNested(): void
    {
        $user        = new AtomicSetUser('Maciej');
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $user->phonebooks[] = $privateBook;
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with a nested embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(1, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertCount(1, $privateBook->getPhonenumbers());
        self::assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->addPhonenumber(new Phonenumber('87654321'));
        $publicBook = new Phonebook('Public');
        $publicBook->addPhonenumber(new Phonenumber('10203040'));
        $user->phonebooks[] = $publicBook;
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating multiple, nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertEquals('Maciej', $user->name);
        self::assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertCount(2, $privateBook->getPhonenumbers());
        self::assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());
        self::assertEquals('87654321', $privateBook->getPhonenumbers()->get(1)->getPhonenumber());
        $publicBook = $user->phonebooks[1];
        self::assertCount(1, $publicBook->getPhonenumbers());
        self::assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->getPhonenumbers()->clear();
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Clearing a nested embed-many collection requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertEmpty($privateBook->getPhonenumbers());
        $publicBook = $user->phonebooks[1];
        self::assertCount(1, $publicBook->getPhonenumbers());
        self::assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());
    }

    public function testWeNeedToGoDeeper(): void
    {
        $user                                          = new AtomicSetUser('Maciej');
        $user->inception[0]                            = new AtomicSetInception('start');
        $user->inception[0]->one                       = new AtomicSetInception('start.one');
        $user->inception[0]->one->many[]               = new AtomicSetInception('start.one.many.0');
        $user->inception[0]->one->many[]               = new AtomicSetInception('start.one.many.1');
        $user->inception[0]->one->one                  = new AtomicSetInception('start.one.one');
        $user->inception[0]->one->one->many[]          = new AtomicSetInception('start.one.one.many.0');
        $user->inception[0]->one->one->many[0]->many[] = new AtomicSetInception('start.one.one.many.0.many.0');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(1, $user->inception);
        self::assertEquals('start', $user->inception[0]->value);
        self::assertNotNull($user->inception[0]->one);
        self::assertEquals('start.one', $user->inception[0]->one->value);
        self::assertCount(2, $user->inception[0]->one->many);
        self::assertEquals('start.one.many.0', $user->inception[0]->one->many[0]->value);
        self::assertEquals('start.one.many.1', $user->inception[0]->one->many[1]->value);
        self::assertNotNull($user->inception[0]->one->one);
        self::assertEquals('start.one.one', $user->inception[0]->one->one->value);
        self::assertCount(1, $user->inception[0]->one->one->many);
        self::assertEquals('start.one.one.many.0', $user->inception[0]->one->one->many[0]->value);
        self::assertCount(1, $user->inception[0]->one->one->many[0]->many);
        self::assertEquals('start.one.one.many.0.many.0', $user->inception[0]->one->one->many[0]->many[0]->value);

        unset($user->inception[0]->one->many[0]);
        $user->inception[0]->one->many[]               = new AtomicSetInception('start.one.many.2');
        $user->inception[0]->one->one->many[0]->many[] = new AtomicSetInception('start.one.one.many.0.many.1');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating nested collections on various levels requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(1, $user->inception);
        self::assertEquals('start', $user->inception[0]->value);
        self::assertNotNull($user->inception[0]->one);
        self::assertEquals('start.one', $user->inception[0]->one->value);
        self::assertCount(2, $user->inception[0]->one->many);
        /* Note: Since the "start.one.many" collection uses a pushAll strategy,
         * "start.one.many.1" is reindexed at 0 after fetching. Before the last
         * flush (when we unset "start.one.many.0"), it would still have been
         * accessible via index 1.
         */
        self::assertEquals('start.one.many.1', $user->inception[0]->one->many[0]->value);
        self::assertEquals('start.one.many.2', $user->inception[0]->one->many[1]->value);
        self::assertNotNull($user->inception[0]->one->one);
        self::assertEquals('start.one.one', $user->inception[0]->one->one->value);
        self::assertCount(1, $user->inception[0]->one->one->many);
        self::assertEquals('start.one.one.many.0', $user->inception[0]->one->one->many[0]->value);
        self::assertCount(2, $user->inception[0]->one->one->many[0]->many);
        self::assertEquals('start.one.one.many.0.many.0', $user->inception[0]->one->one->many[0]->many[0]->value);
        self::assertEquals('start.one.one.many.0.many.1', $user->inception[0]->one->one->many[0]->many[1]->value);
    }

    public function testUpdatingNestedCollectionWhileDeletingParent(): void
    {
        $user                                 = new AtomicSetUser('Jon');
        $user->inception[0]                   = new AtomicSetInception('start');
        $user->inception[0]->many[0]          = new AtomicSetInception('start.many.0');
        $user->inception[0]->many[0]->many[0] = new AtomicSetInception('start.many.0.many.0');
        $user->inception[0]->many[1]          = new AtomicSetInception('start.many.1');
        $user->inception[0]->many[1]->many[0] = new AtomicSetInception('start.many.1.many.0');
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a document with nested embed-many collections requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(1, $user->inception);
        self::assertEquals('start', $user->inception[0]->value);
        self::assertCount(2, $user->inception[0]->many);
        self::assertEquals('start.many.0', $user->inception[0]->many[0]->value);
        self::assertCount(1, $user->inception[0]->many[0]->many);
        self::assertEquals('start.many.0.many.0', $user->inception[0]->many[0]->many[0]->value);
        self::assertEquals('start.many.1', $user->inception[0]->many[1]->value);
        self::assertCount(1, $user->inception[0]->many[0]->many);
        self::assertEquals('start.many.1.many.0', $user->inception[0]->many[1]->many[0]->value);

        $user->inception[0]->many[0]->many[0]->value = 'start.many.0.many.0-changed';
        $user->inception[0]->many[0]->many[1]        = new AtomicSetInception('start.many.0.many.1');
        $user->inception[0]->many[0]->many->clear();
        $user->inception[0]->many[1]->many[1] = new AtomicSetInception('start.many.1.many.1');
        $user->inception[0]->many[1]          = new AtomicSetInception('start.many.1');
        $user->inception[0]->many[1]->many[0] = new AtomicSetInception('start.many.1.many.0-new');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating nested collections while deleting parents requires one query');
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        self::assertCount(1, $user->inception);
        self::assertEquals('start', $user->inception[0]->value);
        self::assertCount(2, $user->inception[0]->many);
        self::assertEquals('start.many.0', $user->inception[0]->many[0]->value);
        self::assertEmpty($user->inception[0]->many[0]->many);
        self::assertEquals('start.many.1', $user->inception[0]->many[1]->value);
        self::assertCount(1, $user->inception[0]->many[1]->many);
        self::assertEquals('start.many.1.many.0-new', $user->inception[0]->many[1]->many[0]->value);
    }

    public function testAtomicRefMany(): void
    {
        $malarzm = new AtomicSetUser('Maciej');
        $jmikola = new AtomicSetUser('Jeremy');
        $jonwage = new AtomicSetUser('Jon');

        $this->dm->persist($malarzm);
        $this->dm->persist($jmikola);
        $this->dm->persist($jonwage);
        $this->dm->flush();
        $this->logger->clear();

        $malarzm->friends[] = $jmikola;
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating empty atomic reference many requires one query');
        $this->dm->clear();

        $malarzm = $this->dm->find(get_class($malarzm), $malarzm->id);
        self::assertCount(1, $malarzm->friends);
        self::assertEquals('Jeremy', $malarzm->friends[0]->name);

        $jonwage            = $this->dm->find(get_class($jonwage), $jonwage->id);
        $malarzm->friends[] = $jonwage;
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Updating existing atomic reference many requires one query');
        $this->dm->clear();

        $malarzm = $this->dm->find(get_class($malarzm), $malarzm->id);
        self::assertCount(2, $malarzm->friends);
        self::assertEquals('Jeremy', $malarzm->friends[0]->name);
        self::assertEquals('Jon', $malarzm->friends[1]->name);

        $malarzm->friends->clear();
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Clearing atomic reference many requires one query');
        $this->dm->clear();
    }

    public function testAtomicSetUpdatesAllNestedCollectionsInOneQuery(): void
    {
        // Create a book which has one chapter with one page.
        $chapter1 = new Chapter();
        $chapter1->pages->add(new Page(1));
        $book = new Book();
        $book->chapters->add($chapter1);

        $this->dm->persist($book);

        // Saving this book should result in 1 query since we use strategy="atomicSet"
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Inserting a book with one chapter and page requires one query');

        // Simulate another PHP request which loads this record and tries to add an embedded document two levels deep...
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);

        // Now we add a new "page" to the only chapter in this book.
        $firstChapter = $book->chapters->first();
        $firstChapter->pages->add(new Page(2));

        // Updating this book should result in 1 query since we use strategy="atomicSet"
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(1, $this->logger, 'Adding a page to the first chapter of the book requires one query');

        // this is failing if lifecycle callback postUpdate is recomputing change set
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);
        self::assertEquals(2, $book->chapters->first()->pages->count(), 'Two page objects are expected in the first chapter of the book.');
    }

    public function testReplacementOfEmbedManyElements(): void
    {
        // Create a book with a single chapter.
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);

        $firstChapter       = $book->chapters->first();
        $firstChapter->name = 'First chapter A';

        // Developers commonly attempt to replace the contents of an EmbedMany with a new ArrayCollection like this:
        /** @var ArrayCollection<int, Chapter> $replacementChapters */
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new Chapter('Second chapter B'));
        $book->chapters = $replacementChapters;

        $this->dm->flush(); // <- Currently getting "Cannot update 'chapters' and 'chapters' at the same time" failures.

        // Simulate another PHP request.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);

        // Verify we see chapters A and B.
        self::assertEquals('First chapter A', $book->chapters[0]->name);
        self::assertEquals('Second chapter B', $book->chapters[1]->name);
    }

    public function testReplacementOfIdentifiedEmbedManyElements(): void
    {
        $book = new Book();
        $book->identifiedChapters->add(new IdentifiedChapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book               = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);
        $firstChapter       = $book->identifiedChapters->first();
        $firstChapter->name = 'First chapter A';
        /** @var ArrayCollection<int, IdentifiedChapter> $replacementChapters */
        $replacementChapters = new ArrayCollection();
        $replacementChapters->add($firstChapter);
        $replacementChapters->add(new IdentifiedChapter('Second chapter B'));
        $book->identifiedChapters = $replacementChapters;

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);
        self::assertEquals('First chapter A', $book->identifiedChapters[0]->name);
        self::assertEquals('Second chapter B', $book->identifiedChapters[1]->name);
    }

    public function testOnlyEmbeddedDocumentUpdated(): void
    {
        // Create a book with a single chapter.
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        // Save it.
        $this->dm->persist($book);
        $this->dm->flush();

        // Simulate another PHP request which loads this record.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);

        // Modify the chapter's name.
        $book->chapters->first()->name = 'First chapter A';

        $this->dm->flush();

        // Simulate another PHP request & verify the change was saved.
        $this->dm->clear();
        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);

        self::assertEquals('First chapter A', $book->chapters[0]->name, 'The chapter title failed to update.');
    }

    public function testUpdatedEmbeddedDocumentAndDirtyCollectionInside(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('A'));

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $book               = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);
        $firstChapter       = $book->chapters->first();
        $firstChapter->name = 'Apple';

        // Add some pages.
        $firstChapter->pages->add(new Page(1));
        $firstChapter->pages->add(new Page(2));

        $this->dm->flush();
        $this->dm->clear();

        $book = $this->dm->getRepository(Book::CLASSNAME)->findOneBy(['_id' => $book->id]);
        self::assertEquals(2, $book->chapters->first()->pages->count());
    }
}

/** @ODM\Document */
class AtomicSetUser
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\Field(type="int")
     * @ODM\Version
     *
     * @var int
     */
    public $version = 1;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $surname;

    /**
     * @ODM\EmbedMany(strategy="atomicSet", targetDocument=Documents\Phonenumber::class)
     *
     * @var Collection<int, Phonenumber>
     */
    public $phonenumbers;

    /**
     * @ODM\EmbedMany(strategy="atomicSetArray", targetDocument=Documents\Phonenumber::class)
     *
     * @var Collection<int, Phonenumber>
     */
    public $phonenumbersArray;

    /**
     * @ODM\EmbedMany(strategy="atomicSet", targetDocument=Documents\Phonebook::class)
     *
     * @var Collection<int, Phonebook>
     */
    public $phonebooks;

    /**
     * @ODM\EmbedMany(strategy="atomicSet", targetDocument=AtomicSetInception::class)
     *
     * @var Collection<int, AtomicSetInception>
     */
    public $inception;

    /**
     * @ODM\ReferenceMany(strategy="atomicSetArray", targetDocument=AtomicSetUser::class)
     *
     * @var Collection<int, AtomicSetUser>|array<AtomicSetUser>
     */
    public $friends;

    public function __construct(string $name)
    {
        $this->name              = $name;
        $this->phonenumbers      = new ArrayCollection();
        $this->phonenumbersArray = new ArrayCollection();
        $this->phonebooks        = new ArrayCollection();
        $this->friends           = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class AtomicSetInception
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $value;

    /**
     * @ODM\EmbedOne(targetDocument=AtomicSetInception::class)
     *
     * @var AtomicSetInception|null
     */
    public $one;

    /**
     * @ODM\EmbedMany(targetDocument=AtomicSetInception::class)
     *
     * @var Collection<int, AtomicSetInception>
     */
    public $many;

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->many  = new ArrayCollection();
    }
}
