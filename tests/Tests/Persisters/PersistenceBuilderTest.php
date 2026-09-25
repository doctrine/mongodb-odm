<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Address;
use Documents\Book;
use Documents\Chapter;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\Ecommerce\Basket;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Order;
use Documents\Functional\EmbeddedWhichReferences;
use Documents\Functional\EmbedNamed;
use Documents\Functional\FavoritesUser;
use Documents\Functional\NotSaved;
use Documents\Functional\NotSavedEmbedded;
use Documents\Functional\OpenDiscriminatorDocument;
use Documents\Functional\Reference;
use Documents\Functional\SameCollection1;
use Documents\Functional\SameCollection2;
use Documents\Functional\Ticket\GH683\ParentDocument as GH683ParentDocument;
use Documents\Functional\Ticket\GH683\UnmappedEmbedded;
use Documents\Group;
use Documents\Message;
use Documents\Page;
use Documents\Phonenumber;
use Documents\Profile;
use Documents\Project;
use Documents\Strategy;
use Documents\UnmappedSubProject;
use Documents\User;
use Documents\UserUpsert;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use stdClass;

use function array_keys;

class PersistenceBuilderTest extends BaseTestCase
{
    private PersistenceBuilder $pb;

    public function setUp(): void
    {
        parent::setUp();

        $this->pb = $this->dm->getUnitOfWork()->getPersistenceBuilder();
    }

    public function tearDown(): void
    {
        unset($this->pb);

        parent::tearDown();
    }

    public function testQueryBuilderUpdateWithDiscriminatorMap(): void
    {
        $testCollection = new SameCollection1();
        $id             = '4f28aa84acee413889000001';

        $testCollection->id   = $id;
        $testCollection->name = 'First entry';
        $this->dm->persist($testCollection);
        $this->dm->flush();
        $this->uow->computeChangeSets();

        $qb = $this->dm->createQueryBuilder(SameCollection1::class);
        $qb->updateOne()
            ->field('ok')->set(true)
            ->field('test')->set('OK! TEST')
            ->field('id')->equals($id);

        $query = $qb->getQuery();
        $query->execute();

        $this->dm->refresh($testCollection);

        self::assertEquals('OK! TEST', $testCollection->test);
    }

    public function testFindWithOrOnCollectionWithDiscriminatorMap(): void
    {
        $sameCollection1  = new SameCollection1();
        $sameCollection2a = new SameCollection2();
        $sameCollection2b = new SameCollection2();
        $ids              = [
            '4f28aa84acee413889000001',
            '4f28aa84acee41388900002a',
            '4f28aa84acee41388900002b',
        ];

        $sameCollection1->id   = $ids[0];
        $sameCollection1->name = 'First entry in SameCollection1';
        $sameCollection1->test = 'test';
        $this->dm->persist($sameCollection1);
        $this->dm->flush();

        $sameCollection2a->id   = $ids[1];
        $sameCollection2a->name = 'First entry in SameCollection2';
        $sameCollection2a->ok   = true;
        $this->dm->persist($sameCollection2a);
        $this->dm->flush();

        $sameCollection2b->id   = $ids[2];
        $sameCollection2b->name = 'Second entry in SameCollection2';
        $sameCollection2b->w00t = '!!';
        $this->dm->persist($sameCollection2b);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        $qb = $this->dm->createQueryBuilder(SameCollection2::class);
        $qb
            ->field('id')->in($ids)
            ->select('id')->hydrate(false);
        $query   = $qb->getQuery();
        $debug   = $query->debug('query');
        $results = $query->execute();

        self::assertInstanceOf(Iterator::class, $results);

        $targetClass = $this->dm->getClassMetadata(SameCollection2::class);
        $identifier1 = $targetClass->getDatabaseIdentifierValue($ids[1]);

        self::assertEquals($identifier1, $debug['_id']['$in'][1]);

        self::assertCount(2, $results->toArray());
    }

    public function testPrepareUpdateDataDoesNotIncludeId(): void
    {
        $article        = new CmsArticle();
        $article->topic = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article        = $this->dm->getRepository($article::class)->find($article->id);
        $article->id    = null;
        $article->topic = 'test';

        $this->uow->computeChangeSets();
        $data = $this->pb->prepareUpdateData($article);
        self::assertFalse(isset($data['$unset']['_id']));
    }

    public function testPrepareInsertDataWithCreatedReferenceOne(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $comment          = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            'article' => [
                '$id' => new ObjectId($article->id),
                '$ref' => 'CmsArticle',
            ],
            'nullableField' => null,
        ];
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareInsertDataWithFetchedReferenceOne(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article          = $this->dm->find($article::class, $article->id);
        $comment          = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            'article' => [
                '$id' => new ObjectId($article->id),
                '$ref' => 'CmsArticle',
            ],
            'nullableField' => null,
        ];
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareUpsertData(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article          = $this->dm->find($article::class, $article->id);
        $comment          = new CmsComment();
        $comment->topic   = 'test';
        $comment->text    = 'text';
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            '$set' => [
                'topic' => 'test',
                'text' => 'text',
                'article' => [
                    '$id' => new ObjectId($article->id),
                    '$ref' => 'CmsArticle',
                ],
                '_id' => new ObjectId($comment->id),
            ],
            '$setOnInsert' => ['nullableField' => null],
        ];
        self::assertEquals($expectedData, $this->pb->prepareUpsertData($comment));
    }

    /** @param array<string, mixed> $expectedData */
    #[DataProvider('getDocumentsAndExpectedData')]
    public function testPrepareInsertData(object $document, array $expectedData): void
    {
        $this->dm->persist($document);
        $this->uow->computeChangeSets();
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($document));
    }

    /**
     * Provides data for @see PersistenceBuilderTest::testPrepareInsertData()
     * Returns arrays of array(document => expected data)
     */
    public static function getDocumentsAndExpectedData(): array
    {
        return [
            [new ConfigurableProduct('Test Product'), ['name' => 'Test Product']],
            [new Currency('USD', 1), ['name' => 'USD', 'multiplier' => 1]],
            [new Order(), ['products' => []]],
            [new Basket(), []],
        ];
    }

    /**
     * @param array<string, mixed> $expectedData
     * @param array<string, mixed> $preparedData
     */
    private function assertDocumentInsertData(array $expectedData, array $preparedData): void
    {
        foreach ($preparedData as $key => $value) {
            if ($key === '_id') {
                self::assertInstanceOf(ObjectId::class, $value);
                continue;
            }

            self::assertEquals($expectedData[$key], $value);
        }

        if (! isset($preparedData['_id'])) {
            $this->fail('insert data should always contain id');
        }

        unset($preparedData['_id']);
        self::assertEquals(array_keys($expectedData), array_keys($preparedData));
    }

    public function testAdvancedQueriesOnReferenceWithDiscriminatorMap(): void
    {
        $article        = new CmsArticle();
        $article->id    = '4f8373f952fbfe7411000001';
        $article->title = 'advanced queries test';
        $this->dm->persist($article);
        $this->dm->flush();

        $comment          = new CmsComment();
        $comment->id      = '4f8373f952fbfe7411000002';
        $comment->article = $article;
        $this->dm->persist($comment);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        $articleId = $article->id;
        $commentId = $comment->id;

        $qb = $this->dm->createQueryBuilder(CmsComment::class);
        $qb
            ->field('article.id')->in([$articleId]);
        $query   = $qb->getQuery();
        $results = $query->execute();

        self::assertInstanceOf(Iterator::class, $results);

        $singleResult = $query->getSingleResult();
        self::assertInstanceOf(CmsComment::class, $singleResult);

        self::assertEquals($commentId, $singleResult->id);

        self::assertInstanceOf(CmsArticle::class, $singleResult->article);

        self::assertEquals($articleId, $singleResult->article->id);
        self::assertCount(1, $results->toArray());
    }

    public function testPrepareUpdateDataUnsetsNonNullableFieldOnNull(): void
    {
        $user = new User();
        $user->setUsername('alice');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->setProtectedProperty($user, 'username', null);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertTrue($data['$unset']['username']);
        self::assertArrayNotHasKey('username', $data['$set'] ?? []);
    }

    public function testPrepareUpdateDataSetsNullableFieldToNull(): void
    {
        $user = new User();
        $this->setProtectedProperty($user, 'disabledAt', new DateTime('2024-01-01'));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->setProtectedProperty($user, 'disabledAt', null);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertNull($data['$set']['disable-at']);
    }

    public function testPrepareUpdateDataIncrementStrategyComputesDelta(): void
    {
        $user = new User();
        $user->setCount(1);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setCount(5);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertSame(4, $data['$inc']['count']);
    }

    public function testPrepareUpdateDataEmbedOneNewInstanceSetsWholeDocument(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $address = new Address();
        $address->setAddress('123 Main St');
        $user->setAddress($address);
        $this->dm->persist($address);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertSame('123 Main St', $data['$set']['address']['address']);
    }

    public function testPrepareUpdateDataEmbedOneExistingInstanceRecursesWithDottedKeys(): void
    {
        $user    = new User();
        $address = new Address();
        $address->setAddress('123 Main St');
        $address->setCity('Old City');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->getAddress()->setCity('New City');
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertSame('New City', $data['$set']['address.city']);
    }

    public function testPrepareUpdateDataReferenceOneFieldIsSet(): void
    {
        $user    = new User();
        $profile = new Profile();
        $user->setProfile($profile);
        $this->dm->persist($user);
        $this->dm->flush();

        $newProfile = new Profile();
        $user->setProfile($newProfile);
        $this->dm->persist($newProfile);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertEquals(['$ref' => 'Profile', '$id' => new ObjectId($newProfile->getProfileId())], $data['$set']['profile']);
    }

    public function testPrepareUpdateDataEmbedManyAtomicStrategySetsWholeCollectionWhenDirty(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->dm->flush();

        $book->chapters->add(new Chapter('Chapter Two'));
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($book);
        self::assertCount(2, $data['$set']['chapters']);
        self::assertSame('Chapter One', $data['$set']['chapters'][0]['name']);
        self::assertSame('Chapter Two', $data['$set']['chapters'][1]['name']);
    }

    public function testPrepareUpdateDataEmbedManyAtomicStrategyUnsetsWhenScheduledForDeletion(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->dm->flush();

        $oldCollection  = $book->chapters;
        $book->chapters = new ArrayCollection();
        $this->uow->computeChangeSets();

        self::assertTrue($this->uow->isCollectionScheduledForDeletion($oldCollection));

        $data = $this->pb->prepareUpdateData($book);
        self::assertTrue($data['$unset']['chapters']);
        self::assertFalse($this->uow->isCollectionScheduledForDeletion($oldCollection));
    }

    public function testPrepareUpdateDataEmbedManyNonAtomicStrategyDiffsPerElement(): void
    {
        $strategy             = new Strategy();
        $message              = new Message('Original');
        $strategy->messages[] = $message;
        $this->dm->persist($strategy);
        $this->dm->flush();

        $strategy->messages[0]->setName('Updated');
        $strategy->messages[] = new Message('Brand new');
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($strategy);
        self::assertSame('Updated', $data['$set']['messages.0.name']);
        self::assertArrayNotHasKey('messages.1.name', $data['$set']);
    }

    public function testPrepareUpdateDataNonAtomicReferenceManyIsIgnored(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $group = new Group('admins');
        $user->addGroup($group);
        $this->dm->persist($group);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpdateData($user);
        self::assertArrayNotHasKey('groups', $data['$set'] ?? []);
        self::assertArrayNotHasKey('groups', $data['$unset'] ?? []);
    }

    public function testPrepareUpdateDataEmbedManyAtomicStrategyUnsetsWhenClearedInPlace(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->dm->flush();

        $book->chapters->clear();
        $this->uow->computeChangeSets();

        self::assertTrue($this->uow->isCollectionScheduledForDeletion($book->chapters));

        $data = $this->pb->prepareUpdateData($book);
        self::assertTrue($data['$unset']['chapters']);
        self::assertFalse($this->uow->isCollectionScheduledForDeletion($book->chapters));
    }

    public function testPrepareUpdateDataAppliesScheduledCollectionUpdateNotInChangeSet(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->dm->flush();
        $this->uow->computeChangeSets();

        $this->uow->scheduleCollectionUpdate($book->chapters);

        $data = $this->pb->prepareUpdateData($book);
        self::assertCount(1, $data['$set']['chapters']);
    }

    public function testPrepareUpdateDataAppliesScheduledCollectionDeletionNotInChangeSet(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->dm->flush();
        $this->uow->computeChangeSets();

        $this->uow->scheduleCollectionDeletion($book->chapters);

        $data = $this->pb->prepareUpdateData($book);
        self::assertTrue($data['$unset']['chapters']);
        self::assertFalse($this->uow->isCollectionScheduledForDeletion($book->chapters));
    }

    public function testPrepareUpsertDataNonNullableNullFieldIsOmittedEntirely(): void
    {
        $user = new User();
        $this->setProtectedProperty($user, 'username', null);
        $this->dm->persist($user);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($user);
        self::assertArrayNotHasKey('username', $data['$set'] ?? []);
        self::assertArrayNotHasKey('username', $data['$setOnInsert'] ?? []);
    }

    public function testPrepareUpsertDataIncrementStrategyComputesDelta(): void
    {
        $user        = new UserUpsert();
        $user->count = 1;
        $this->dm->persist($user);
        $this->dm->flush();

        $user->count = 5;
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($user);
        self::assertSame(4, $data['$inc']['count']);
    }

    public function testPrepareUpsertDataSetsDiscriminatorValue(): void
    {
        $user = new UserUpsert();
        $this->dm->persist($user);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($user);
        self::assertSame('user', $data['$set']['discriminator']);
    }

    public function testPrepareUpsertDataEmbedOneNewInstanceSetsWholeDocument(): void
    {
        $user    = new User();
        $address = new Address();
        $address->setAddress('123 Main St');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->persist($address);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($user);
        self::assertSame('123 Main St', $data['$set']['address']['address']);
    }

    public function testPrepareUpsertDataEmbedOneExistingInstanceRecursesWithDottedKeys(): void
    {
        $user    = new User();
        $address = new Address();
        $address->setAddress('123 Main St');
        $address->setCity('Old City');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->getAddress()->setCity('New City');
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($user);
        self::assertSame('New City', $data['$set']['address.city']);
    }

    public function testPrepareUpsertDataAtomicCollectionSetsWholeCollectionWhenDirty(): void
    {
        $book = new Book();
        $book->chapters->add(new Chapter('Chapter One'));
        $this->dm->persist($book);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareUpsertData($book);
        self::assertCount(1, $data['$set']['chapters']);
        self::assertSame('Chapter One', $data['$set']['chapters'][0]['name']);
    }

    public function testPrepareInsertDataResolvesMappedDiscriminatorValue(): void
    {
        $project = new Project('My Project');
        $this->dm->persist($project);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareInsertData($project);
        self::assertSame('project', $data['type']);
    }

    public function testPrepareInsertDataThrowsForClassMissingFromOwnDiscriminatorMap(): void
    {
        $project = new UnmappedSubProject('Unmapped');
        $this->dm->persist($project);
        $this->uow->computeChangeSets();

        $this->expectException(MappingException::class);
        $this->pb->prepareInsertData($project);
    }

    public function testPrepareInsertDataFallsBackToClassNameWhenNoDiscriminatorMapIsDeclared(): void
    {
        $document = new OpenDiscriminatorDocument();
        $this->dm->persist($document);
        $this->uow->computeChangeSets();

        $data = $this->pb->prepareInsertData($document);
        self::assertSame(OpenDiscriminatorDocument::class, $data['type']);
    }

    public function testPrepareEmbeddedDocumentValueSkipsNotSavedFields(): void
    {
        $embedded           = new NotSavedEmbedded();
        $embedded->name     = 'kept';
        $embedded->notSaved = 'dropped';

        $class   = $this->dm->getClassMetadata(NotSaved::class);
        $mapping = $class->fieldMappings['embedded'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $embedded);
        self::assertSame('kept', $value['name']);
        self::assertArrayNotHasKey('notSaved', $value);
    }

    public function testPrepareEmbeddedDocumentValueUsesClassNameAsDiscriminatorWhenNoTargetDocument(): void
    {
        $favoritesUser = new FavoritesUser();
        $class         = $this->dm->getClassMetadata(FavoritesUser::class);
        $mapping       = $class->fieldMappings['embed'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, new Group('admins'));
        self::assertSame('admins', $value['name']);
        self::assertSame(Group::class, $value[$mapping['discriminatorField']]);
    }

    public function testPrepareEmbeddedDocumentValueReturnsObjectForEmptyResult(): void
    {
        $class   = $this->dm->getClassMetadata(NotSaved::class);
        $mapping = $class->fieldMappings['embedded'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, new NotSavedEmbedded());
        self::assertIsObject($value);
        self::assertNotInstanceOf(NotSavedEmbedded::class, $value);
    }

    public function testPrepareEmbeddedDocumentValueRecursesIntoNestedEmbedOne(): void
    {
        $address = new Address();
        $address->setAddress('Top');
        $sub = new Address();
        $sub->setAddress('Sub');
        $address->setSubAddress($sub);

        $class   = $this->dm->getClassMetadata(User::class);
        $mapping = $class->fieldMappings['address'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $address);
        self::assertSame('Sub', $value['subAddress']['address']);
    }

    public function testPrepareEmbeddedDocumentValueIncludesNestedReferenceManyCollection(): void
    {
        $reference = new Reference();
        $this->dm->persist($reference);
        $this->dm->flush();

        $embedded                   = new EmbeddedWhichReferences();
        $embedded->referencedDocs[] = $reference;

        $class   = $this->dm->getClassMetadata(EmbedNamed::class);
        $mapping = $class->fieldMappings['embeddedDoc'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $embedded);
        self::assertCount(1, $value['reference_docs']);
    }

    public function testPrepareEmbeddedDocumentValueSkipsNestedCollectionScheduledForDeletion(): void
    {
        $reference = new Reference();
        $this->dm->persist($reference);

        $document                                = new EmbedNamed();
        $document->embeddedDoc                   = new EmbeddedWhichReferences();
        $document->embeddedDoc->referencedDocs[] = $reference;
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(EmbedNamed::class, $document->id);
        $this->uow->scheduleCollectionDeletion($document->embeddedDoc->referencedDocs);

        $class   = $this->dm->getClassMetadata(EmbedNamed::class);
        $mapping = $class->fieldMappings['embeddedDoc'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $document->embeddedDoc);
        self::assertEquals(new stdClass(), $value);
    }

    public function testPrepareEmbeddedDocumentValueWrapsRawCollectionIntoPersistentCollection(): void
    {
        $chapter = new Chapter('Test Chapter');
        $chapter->pages->add(new Page(1));

        $class   = $this->dm->getClassMetadata(Book::class);
        $mapping = $class->fieldMappings['chapters'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $chapter, true);
        self::assertCount(1, $value['pages']);
        self::assertSame(1, $value['pages'][0]['number']);
    }

    public function testPrepareAssociatedDocumentValueDelegatesToEmbeddedPreparation(): void
    {
        $class   = $this->dm->getClassMetadata(User::class);
        $mapping = $class->fieldMappings['address'];

        $address = new Address();
        $address->setAddress('123 Main St');

        $value = $this->pb->prepareAssociatedDocumentValue($mapping, $address);
        self::assertSame('123 Main St', $value['address']);
    }

    public function testPrepareAssociatedDocumentValueDelegatesToReferencePreparation(): void
    {
        $profile = new Profile();
        $this->dm->persist($profile);
        $this->dm->flush();

        $class   = $this->dm->getClassMetadata(User::class);
        $mapping = $class->fieldMappings['profile'];

        $value = $this->pb->prepareAssociatedDocumentValue($mapping, $profile);
        self::assertEquals(['$ref' => 'Profile', '$id' => new ObjectId($profile->getProfileId())], $value);
    }

    public function testPrepareEmbeddedDocumentValueFallsBackToClassNameWhenNoDiscriminatorMapIsDeclared(): void
    {
        $class   = $this->dm->getClassMetadata(User::class);
        $mapping = $class->fieldMappings['address'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, new OpenDiscriminatorDocument());
        self::assertSame(OpenDiscriminatorDocument::class, $value['type']);
    }

    public function testPrepareEmbeddedDocumentValueThrowsForClassMissingFromOwnDiscriminatorMap(): void
    {
        $class   = $this->dm->getClassMetadata(GH683ParentDocument::class);
        $mapping = $class->fieldMappings['embedOne'];

        $this->expectException(MappingException::class);
        $this->pb->prepareEmbeddedDocumentValue($mapping, new UnmappedEmbedded());
    }

    public function testPreparePersistentCollectionWrapsPlainArrayIntoArrayCollection(): void
    {
        $chapter = new Chapter('Test Chapter');
        $this->setProtectedProperty($chapter, 'pages', [new Page(1)]);

        $class   = $this->dm->getClassMetadata(Book::class);
        $mapping = $class->fieldMappings['chapters'];

        $value = $this->pb->prepareEmbeddedDocumentValue($mapping, $chapter, true);
        self::assertCount(1, $value['pages']);
        self::assertSame(1, $value['pages'][0]['number']);
    }

    public function testPrepareAssociatedDocumentValueThrowsWhenMappingIsNeitherEmbeddedNorReference(): void
    {
        // A plain scalar field mapping carries neither the 'embedded' nor the 'reference' key.
        $mapping = $this->dm->getClassMetadata(User::class)->fieldMappings['username'];

        $this->expectException(InvalidArgumentException::class);
        $this->pb->prepareAssociatedDocumentValue($mapping, new stdClass());
    }

    public function testGetCollectionValuePrepareCallbackReturnsEmbeddedPreparerForEmbedManyMapping(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $phonenumber = new Phonenumber('12345');
        $coll        = $user->getPhonenumbers();
        self::assertInstanceOf(PersistentCollectionInterface::class, $coll);

        $callback = $this->pb->getCollectionValuePrepareCallback($coll);

        self::assertEquals(
            $this->pb->prepareEmbeddedDocumentValue($coll->getMapping(), $phonenumber),
            $callback($phonenumber),
        );
    }

    public function testGetCollectionValuePrepareCallbackReturnsReferencePreparerForReferenceManyMapping(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $group = new Group('admins');
        $this->dm->persist($group);
        $this->dm->flush();

        $coll = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $coll);

        $callback = $this->pb->getCollectionValuePrepareCallback($coll);

        self::assertEquals(
            $this->pb->prepareReferencedDocumentValue($coll->getMapping(), $group),
            $callback($group),
        );
    }

    public function testPrepareCollectionInsertPayloadWrapsPreparedValuesInEach(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $group1 = new Group('one');
        $group2 = new Group('two');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $coll = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $coll);

        // Non-sequential keys mimic a real insert diff: array_values must
        // reindex the result rather than leaking the diff's original keys.
        $payload = $this->pb->prepareCollectionInsertPayload($coll, [2 => $group1, 5 => $group2]);

        self::assertEquals(
            [
                '$each' => [
                    $this->pb->prepareReferencedDocumentValue($coll->getMapping(), $group1),
                    $this->pb->prepareReferencedDocumentValue($coll->getMapping(), $group2),
                ],
            ],
            $payload,
        );
    }

    public function testPrepareCollectionInsertPayloadHandlesEmbedManyMapping(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $phone1 = new Phonenumber('111');
        $phone2 = new Phonenumber('222');

        $coll = $user->getPhonenumbers();
        self::assertInstanceOf(PersistentCollectionInterface::class, $coll);

        $payload = $this->pb->prepareCollectionInsertPayload($coll, [3 => $phone1, 7 => $phone2]);

        self::assertEquals(
            [
                '$each' => [
                    $this->pb->prepareEmbeddedDocumentValue($coll->getMapping(), $phone1),
                    $this->pb->prepareEmbeddedDocumentValue($coll->getMapping(), $phone2),
                ],
            ],
            $payload,
        );
    }

    public function testPrepareCollectionInsertPayloadReturnsEmptyEachForEmptyDiff(): void
    {
        $user = new User();
        $this->dm->persist($user);
        $this->dm->flush();

        $coll = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $coll);

        self::assertSame(['$each' => []], $this->pb->prepareCollectionInsertPayload($coll, []));
    }

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        (new ReflectionProperty($object, $property))->setValue($object, $value);
    }
}
