<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Aggregation\Builder as AggregationBuilder;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Proxy\Factory\ProxyFactory;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\BaseCategory;
use Documents\BaseCategoryRepository;
use Documents\BlogPost;
use Documents\Category;
use Documents\CmsPhonenumber;
use Documents\CmsUser;
use Documents\CustomRepository\Document;
use Documents\CustomRepository\Repository;
use Documents\ForumUser;
use Documents\FriendUser;
use Documents\Tournament\Participant;
use Documents\Tournament\ParticipantSolo;
use Documents\User;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Driver\Exception\BulkWriteException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;
use Throwable;

use function uniqid;

class DocumentManagerTest extends BaseTestCase
{
    public function testCustomRepository(): void
    {
        self::assertInstanceOf(Repository::class, $this->dm->getRepository(Document::class));
    }

    public function testCustomRepositoryMappedsuperclass(): void
    {
        self::assertInstanceOf(BaseCategoryRepository::class, $this->dm->getRepository(BaseCategory::class));
    }

    public function testCustomRepositoryMappedsuperclassChild(): void
    {
        self::assertInstanceOf(BaseCategoryRepository::class, $this->dm->getRepository(Category::class));
    }

    public function testGetConnection(): void
    {
        self::assertInstanceOf(Client::class, $this->dm->getClient());
    }

    public function testGetMetadataFactory(): void
    {
        self::assertInstanceOf(ClassMetadataFactory::class, $this->dm->getMetadataFactory());
    }

    public function testGetConfiguration(): void
    {
        self::assertInstanceOf(Configuration::class, $this->dm->getConfiguration());
    }

    public function testGetUnitOfWork(): void
    {
        self::assertInstanceOf(UnitOfWork::class, $this->dm->getUnitOfWork());
    }

    public function testGetProxyFactory(): void
    {
        self::assertInstanceOf(ProxyFactory::class, $this->dm->getProxyFactory());
    }

    public function testGetEventManager(): void
    {
        self::assertInstanceOf(EventManager::class, $this->dm->getEventManager());
    }

    public function testGetSchemaManager(): void
    {
        self::assertInstanceOf(SchemaManager::class, $this->dm->getSchemaManager());
    }

    public function testCreateQueryBuilder(): void
    {
        self::assertInstanceOf(QueryBuilder::class, $this->dm->createQueryBuilder());
    }

    public function testCreateAggregationBuilder(): void
    {
        self::assertInstanceOf(AggregationBuilder::class, $this->dm->createAggregationBuilder(BlogPost::class));
    }

    public function testGetFilterCollection(): void
    {
        self::assertInstanceOf(FilterCollection::class, $this->dm->getFilterCollection());
    }

    public function testGetPartialReference(): void
    {
        $id   = new ObjectId();
        $user = $this->dm->getPartialReference(CmsUser::class, $id);
        self::assertTrue($this->dm->contains($user));
        self::assertEquals($id, $user->id);
        self::assertNull($user->getName());
    }

    public function testDocumentManagerIsClosedAccessor(): void
    {
        self::assertTrue($this->dm->isOpen());
        $this->dm->close();
        self::assertFalse($this->dm->isOpen());
    }

    public static function dataMethodsAffectedByNoObjectArguments(): array
    {
        return [
            ['persist'],
            ['remove'],
            ['merge'],
            ['refresh'],
            ['detach'],
        ];
    }

    #[DataProvider('dataMethodsAffectedByNoObjectArguments')]
    public function testThrowsExceptionOnNonObjectValues(string $methodName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->$methodName(null);
    }

    public static function dataAffectedByErrorIfClosedException(): array
    {
        return [
            ['flush'],
            ['persist'],
            ['remove'],
            ['merge'],
            ['refresh'],
        ];
    }

    #[DataProvider('dataAffectedByErrorIfClosedException')]
    public function testAffectedByErrorIfClosedException(string $methodName): void
    {
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('closed');

        $this->dm->close();
        if ($methodName === 'flush') {
            $this->dm->$methodName();
        } else {
            $this->dm->$methodName(new stdClass());
        }
    }

    public function testCannotCreateDbRefWithoutId(): void
    {
        $d = new User();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot create a DBRef for class Documents\User without an identifier. ' .
            'Have you forgotten to persist/merge the document first?',
        );
        $this->dm->createReference($d, ['storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF]);
    }

    public function testCreateDbRefWithNonNullEmptyId(): void
    {
        $phonenumber              = new CmsPhonenumber();
        $phonenumber->phonenumber = 0;
        $this->dm->persist($phonenumber);

        $dbRef = $this->dm->createReference($phonenumber, ClassMetadataTestUtil::getFieldMapping([
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => CmsPhonenumber::class,
        ]));

        self::assertSame(['$ref' => 'CmsPhonenumber', '$id' => 0], $dbRef);
    }

    public function testDisriminatedSimpleReferenceFails(): void
    {
        $d = new WrongSimpleRefDocument();
        $r = new ParticipantSolo('Maciej');
        $this->dm->persist($r);
        $class = $this->dm->getClassMetadata($d::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Identifier reference must not target document using Single Collection Inheritance, ' .
            'Documents\Tournament\Participant targeted.',
        );
        $this->dm->createReference($r, $class->associationMappings['ref']);
    }

    public function testDifferentStoreAsDbReferences(): void
    {
        $r = new User();
        $this->dm->persist($r);
        $d     = new ReferenceStoreAsDocument();
        $class = $this->dm->getClassMetadata($d::class);

        $dbRef = $this->dm->createReference($r, $class->associationMappings['ref1']);
        self::assertInstanceOf(ObjectId::class, $dbRef);

        $dbRef = $this->dm->createReference($r, $class->associationMappings['ref2']);
        self::assertIsArray($dbRef);
        self::assertCount(2, $dbRef);
        self::assertArrayHasKey('$ref', $dbRef);
        self::assertArrayHasKey('$id', $dbRef);

        $dbRef = $this->dm->createReference($r, $class->associationMappings['ref3']);
        self::assertIsArray($dbRef);
        self::assertCount(3, $dbRef);
        self::assertArrayHasKey('$ref', $dbRef);
        self::assertArrayHasKey('$id', $dbRef);
        self::assertArrayHasKey('$db', $dbRef);

        $dbRef = $this->dm->createReference($r, $class->associationMappings['ref4']);
        self::assertIsArray($dbRef);
        self::assertCount(1, $dbRef);
        self::assertArrayHasKey('id', $dbRef);
    }

    public function testTransactionalWithoutTransactionSupport(): void
    {
        if ($this->supportsTransactions()) {
            $this->markTestSkipped('Test does not apply as transactions are supported');
        }

        $closure = fn () => $this->dm->persist(new User());

        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessageMatches('%transaction%i');

        $this->dm->transactional($closure);
    }

    public function testTransactionalWithTransientError(): void
    {
        $this->skipTestIfNoTransactionSupport();

        $closure = function (): void {
            $firstUser           = new ForumUser();
            $firstUser->username = 'alcaeus';
            $this->uow->persist($firstUser);

            $secondUser           = new ForumUser();
            $secondUser->username = 'jmikola';
            $this->uow->persist($secondUser);

            $friendUser = new FriendUser('GromNaN');
            $this->uow->persist($friendUser);
        };

        // Add a failpoint that triggers a transient error. The transaction will be retried and succeeds
        $this->createTransientFailPoint('insert');

        $this->dm->transactional($closure);

        self::assertSame(
            2,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertSame(
            1,
            $this->dm->getDocumentCollection(FriendUser::class)->countDocuments(),
        );
    }

    public function testTransactionalWithFatalError(): void
    {
        $this->skipTestIfNoTransactionSupport();

        // Create a unique index on the collection to let the second insert fail
        $collection = $this->dm->getDocumentCollection(ForumUser::class);
        $collection->createIndex(['username' => 1], ['unique' => true]);

        $closure = function (): void {
            $firstUser           = new ForumUser();
            $firstUser->username = 'alcaeus';
            $this->uow->persist($firstUser);

            $secondUser           = new ForumUser();
            $secondUser->username = 'alcaeus';
            $this->uow->persist($secondUser);
        };

        try {
            // The second insert will fail due to a duplicate key exception.
            // Since we're using transactions, the first user document should not be saved afterwards
            $this->dm->transactional($closure);
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(11000, $e->getCode()); // Duplicate key
        }

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );
    }

    public function testTransactionalReturnsClosureReturnValue(): void
    {
        $this->skipTestIfNoTransactionSupport();

        $rand    = uniqid();
        $closure = static fn (): string => $rand;

        self::assertSame($rand, $this->dm->transactional($closure));
    }
}

/** @ODM\Document */
class WrongSimpleRefDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Documents\Tournament\Participant::class, storeAs="id")
     *
     * @var Participant|null
     */
    public $ref;
}

/** @ODM\Document */
class ReferenceStoreAsDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="id")
     *
     * @var User|null
     */
    public $ref1;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="dbRef")
     *
     * @var Collection<int, User>
     */
    public $ref2;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="dbRefWithDb")
     *
     * @var Collection<int, User>
     */
    public $ref3;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="ref")
     *
     * @var Collection<int, User>
     */
    public $ref4;
}
