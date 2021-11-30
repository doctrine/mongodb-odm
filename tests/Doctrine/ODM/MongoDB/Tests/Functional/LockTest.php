<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Issue;
use Documents\User;
use InvalidArgumentException;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use function get_class;
use function time;

class LockTest extends BaseTest
{
    public function testOptimisticLockIntSetInitialVersion(): void
    {
        $article = new LockInt('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals(1, $article->version);

        $article->title = 'test';
        $this->dm->flush();

        $this->assertEquals(2, $article->version);
    }

    public function testOptimisticLockIntSetInitialVersionOnUpsert(): void
    {
        $id = new ObjectId();

        $article     = new LockInt('Test LockInt');
        $article->id = $id;

        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertSame($id, $article->id);
        $this->assertEquals(1, $article->version);

        $article->title = 'test';
        $this->dm->flush();

        $this->assertEquals(2, $article->version);
    }

    public function testOptimisticLockingIntThrowsException(): void
    {
        $article = new LockInt('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        // Manually change the version so the next code will cause an exception
        $this->dm->getDocumentCollection(get_class($article))->updateOne(['_id' => new ObjectId($article->id)], ['$set' => ['version' => 5]]);

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->expectException(LockException::class);

        $this->dm->flush();
    }

    public function testMultipleFlushesDoIncrementalUpdates(): void
    {
        $test = new LockInt();

        for ($i = 0; $i < 5; $i++) {
            $test->title = 'test' . $i;
            $this->dm->persist($test);
            $this->dm->flush();

            $this->assertIsInt($test->getVersion());
            $this->assertEquals($i + 1, $test->getVersion());
        }
    }

    public function testLockDateSetsDefaultValue(): LockDate
    {
        $test        = new LockDate();
        $test->title = 'Testing';

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $date1 = $test->version;

        $this->assertInstanceOf('DateTime', $date1);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($date1, $test->version);

        return $test;
    }

    public function testLockDateImmutableSetsDefaultValue(): LockDateImmutable
    {
        $test        = new LockDateImmutable();
        $test->title = 'Testing';

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $date1 = $test->version;

        $this->assertInstanceOf('DateTimeImmutable', $date1);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($date1, $test->version);

        return $test;
    }

    public function testLockDecimal128SetsDefaultValue(): LockDecimal128
    {
        $test        = new LockDecimal128();
        $test->title = 'Testing';

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $version = $test->version;

        $this->assertSame('1', $version);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($version, $test->version);

        return $test;
    }

    public function testLockDateSetsDefaultValueOnUpsert(): LockDate
    {
        $id = new ObjectId();

        $test        = new LockDate();
        $test->title = 'Testing';
        $test->id    = $id;

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $date1 = $test->version;

        $this->assertSame($id, $test->id);
        $this->assertInstanceOf('DateTime', $date1);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($date1, $test->version);

        return $test;
    }

    public function testLockDateImmutableSetsDefaultValueOnUpsert(): LockDateImmutable
    {
        $id = new ObjectId();

        $test        = new LockDateImmutable();
        $test->title = 'Testing';
        $test->id    = $id;

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $date1 = $test->version;

        $this->assertSame($id, $test->id);
        $this->assertInstanceOf('DateTimeImmutable', $date1);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($date1, $test->version);

        return $test;
    }

    public function testLockDecimal128SetsDefaultValueOnUpsert(): LockDecimal128
    {
        $id = new ObjectId();

        $test        = new LockDecimal128();
        $test->title = 'Testing';
        $test->id    = $id;

        $this->assertNull($test->version, 'Pre-Condition');

        $this->dm->persist($test);
        $this->dm->flush();

        $version = $test->version;

        $this->assertSame($id, $test->id);
        $this->assertSame('1', $version);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($version, $test->version);

        return $test;
    }

    public function testLockDateThrowsException(): void
    {
        $article = new LockDate('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        // Manually change the version so the next code will cause an exception
        $this->dm->getDocumentCollection(get_class($article))->updateOne(['_id' => new ObjectId($article->id)], ['$set' => ['version' => new UTCDateTime(time() * 1000 + 600)]]);

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->expectException(LockException::class);

        $this->dm->flush();
    }

    public function testLockDateImmutableThrowsException(): void
    {
        $article = new LockDateImmutable('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        // Manually change the version so the next code will cause an exception
        $this->dm->getDocumentCollection(get_class($article))->updateOne(['_id' => new ObjectId($article->id)], ['$set' => ['version' => new UTCDateTime(time() * 1000 + 600)]]);

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->expectException(LockException::class);

        $this->dm->flush();
    }

    public function testLockDecimal128ThrowsException(): void
    {
        $article = new LockDecimal128('Test LockDecimal128');
        $this->dm->persist($article);
        $this->dm->flush();

        // Manually change the version so the next code will cause an exception
        $this->dm->getDocumentCollection(get_class($article))->updateOne(['_id' => new ObjectId($article->id)], ['$set' => ['version' => new Decimal128('3')]]);

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->expectException(LockException::class);

        $this->dm->flush();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testLockVersionedDocument(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version);
    }

    public function testLockVersionedDocumentMissmatchThrowsException(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->expectException(LockException::class);

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockUnversionedDocumentThrowsException(): void
    {
        $user = new User();
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->expectException(LockException::class);
        $this->expectExceptionMessage('Document Documents\User is not versioned.');

        $this->dm->lock($user, LockMode::OPTIMISTIC);
    }

    public function testLockUnmanagedDocumentThrowsException(): void
    {
        $article = new LockInt();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document is not MANAGED.');

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockPessimisticWrite(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_WRITE);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_WRITE, $check['locked']);
    }

    public function testLockPessimisticRead(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $check['locked']);
    }

    public function testUnlock(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $check['locked']);
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $article->locked);

        $this->dm->unlock($article);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertArrayNotHasKey('locked', $check);
        $this->assertNull($article->locked);
    }

    public function testPessimisticReadLockThrowsExceptionOnRemove(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(LockInt::class);
        $coll->replaceOne(['_id' => new ObjectId($article->id)], ['locked' => LockMode::PESSIMISTIC_READ]);

        $this->expectException(LockException::class);

        $this->dm->remove($article);
        $this->dm->flush();
    }

    public function testPessimisticReadLockThrowsExceptionOnUpdate(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(LockInt::class);
        $coll->replaceOne(['_id' => new ObjectId($article->id)], ['locked' => LockMode::PESSIMISTIC_READ]);

        $this->expectException(LockException::class);

        $article->title = 'changed';
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnRemove(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(LockInt::class);
        $coll->replaceOne(['_id' => new ObjectId($article->id)], ['locked' => LockMode::PESSIMISTIC_WRITE]);

        $this->expectException(LockException::class);

        $this->dm->remove($article);
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnUpdate(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(LockInt::class);
        $coll->replaceOne(['_id' => new ObjectId($article->id)], ['locked' => LockMode::PESSIMISTIC_WRITE]);

        $this->expectException(LockException::class);

        $article->title = 'changed';
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnRead(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(LockInt::class);
        $coll->replaceOne(['_id' => new ObjectId($article->id)], ['locked' => LockMode::PESSIMISTIC_WRITE]);

        $this->expectException(LockException::class);

        $this->dm->clear();
        $article = $this->dm->find(LockInt::class, $article->id);
    }

    public function testPessimisticReadLockFunctional(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $article->title = 'test';
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(LockInt::class)->findOne();
        $this->assertEquals(2, $check['version']);
        $this->assertArrayNotHasKey('locked', $check);
        $this->assertEquals('test', $check['title']);
    }

    public function testPessimisticWriteLockFunctional(): void
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_WRITE);

        $article->title = 'test';
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(LockInt::class)->findOne();
        $this->assertEquals(2, $check['version']);
        $this->assertArrayNotHasKey('locked', $check);
        $this->assertEquals('test', $check['title']);
    }

    public function testInvalidLockDocument(): void
    {
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Invalid lock field type string. Lock field must be int.');
        $this->dm->getClassMetadata(InvalidLockDocument::class);
    }

    public function testInvalidVersionDocument(): void
    {
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Type string does not implement Versionable interface.');
        $this->dm->getClassMetadata(InvalidVersionDocument::class);
    }

    public function testUpdatingCollectionRespectsVersionNumber(): void
    {
        $d = new LockInt('test');
        $d->issues->add(new Issue('hi', 'ohai'));
        $this->dm->persist($d);
        $this->dm->flush();

        // simulate another request updating document in the meantime
        $this->dm->getDocumentCollection(LockInt::class)->updateOne(
            ['_id' => new ObjectId($d->id)],
            ['$set' => ['version' => 2]]
        );

        $d->issues->add(new Issue('oops', 'version mismatch'));
        $this->uow->scheduleCollectionUpdate($d->issues);
        $this->assertInstanceOf(PersistentCollectionInterface::class, $d->issues);
        $this->expectException(LockException::class);
        $this->uow->getCollectionPersister()->update($d, [$d->issues], []);
    }

    public function testDeletingCollectionRespectsVersionNumber(): void
    {
        $d = new LockInt('test');
        $d->issues->add(new Issue('hi', 'ohai'));
        $this->dm->persist($d);
        $this->dm->flush();

        // simulate another request updating document in the meantime
        $this->dm->getDocumentCollection(LockInt::class)->updateOne(
            ['_id' => new ObjectId($d->id)],
            ['$set' => ['version' => 2]]
        );

        $this->assertInstanceOf(PersistentCollectionInterface::class, $d->issues);
        $this->expectException(LockException::class);
        $this->uow->getCollectionPersister()->delete($d, [$d->issues], []);
    }
}

/** @ODM\MappedSuperclass */
abstract class AbstractVersionBase
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;

    /**
     * @ODM\Lock @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $locked;

    /**
     * @ODM\EmbedMany(targetDocument=Issue::class)
     *
     * @var Collection<int, Issue>
     */
    public $issues;

    /** @var int|string|DateTime|DateTimeImmutable|null */
    public $version;

    public function __construct(?string $title = null)
    {
        $this->issues = new ArrayCollection();
        $this->title  = $title;
    }

    /**
     * @return ObjectId|string|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int|string|DateTime|DateTimeImmutable|null
     */
    public function getVersion()
    {
        return $this->version;
    }
}

/** @ODM\Document */
class LockInt extends AbstractVersionBase
{
    /**
     * @ODM\Version @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $version;
}

/** @ODM\Document */
class LockDate extends AbstractVersionBase
{
    /**
     * @ODM\Version @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    public $version;
}

/** @ODM\Document */
class LockDateImmutable extends AbstractVersionBase
{
    /**
     * @ODM\Version @ODM\Field(type="date_immutable")
     *
     * @var DateTimeImmutable|null
     */
    public $version;
}

/** @ODM\Document */
class LockDecimal128 extends AbstractVersionBase
{
    /**
     * @ODM\Version @ODM\Field(type="decimal128")
     *
     * @var string|null
     */
    public $version;
}

/** @ODM\Document */
class InvalidLockDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Lock
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $lock;
}

/** @ODM\Document */
class InvalidVersionDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Version
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $version;
}
