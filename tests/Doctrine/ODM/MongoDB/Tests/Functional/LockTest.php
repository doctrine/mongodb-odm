<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Issue;
use Documents\User;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use function get_class;
use function time;

class LockTest extends BaseTest
{
    public function testOptimisticLockIntSetInitialVersion()
    {
        $article = new LockInt('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals(1, $article->version);

        $article->title = 'test';
        $this->dm->flush();

        $this->assertEquals(2, $article->version);
    }

    public function testOptimisticLockIntSetInitialVersionOnUpsert()
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

    public function testOptimisticLockingIntThrowsException()
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

    public function testMultipleFlushesDoIncrementalUpdates()
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

    public function testLockDateSetsDefaultValue()
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

    public function testLockDateImmutableSetsDefaultValue()
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

    public function testLockDateSetsDefaultValueOnUpsert()
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

    public function testLockDateImmutableSetsDefaultValueOnUpsert()
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

    public function testLockDateThrowsException()
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

    public function testLockDateImmutableThrowsException()
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

    /**
     * @doesNotPerformAssertions
     */
    public function testLockVersionedDocument()
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version);
    }

    public function testLockVersionedDocumentMissmatchThrowsException()
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->expectException(LockException::class);

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockUnversionedDocumentThrowsException()
    {
        $user = new User();
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->expectException(LockException::class);
        $this->expectExceptionMessage('Document Documents\User is not versioned.');

        $this->dm->lock($user, LockMode::OPTIMISTIC);
    }

    public function testLockUnmanagedDocumentThrowsException()
    {
        $article = new LockInt();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document is not MANAGED.');

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockPessimisticWrite()
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_WRITE);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_WRITE, $check['locked']);
    }

    public function testLockPessimisticRead()
    {
        $article        = new LockInt();
        $article->title = 'my article';

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $check['locked']);
    }

    public function testUnlock()
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

    public function testPessimisticReadLockThrowsExceptionOnRemove()
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

    public function testPessimisticReadLockThrowsExceptionOnUpdate()
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

    public function testPessimisticWriteLockThrowExceptionOnRemove()
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

    public function testPessimisticWriteLockThrowExceptionOnUpdate()
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

    public function testPessimisticWriteLockThrowExceptionOnRead()
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

    public function testPessimisticReadLockFunctional()
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

    public function testPessimisticWriteLockFunctional()
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

    public function testInvalidLockDocument()
    {
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Invalid lock field type string. Lock field must be int.');
        $this->dm->getClassMetadata(InvalidLockDocument::class);
    }

    public function testInvalidVersionDocument()
    {
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Invalid version field type string. Version field must be int, integer, date or date_immutable.');
        $this->dm->getClassMetadata(InvalidVersionDocument::class);
    }

    public function testUpdatingCollectionRespectsVersionNumber()
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
        $this->expectException(LockException::class);
        $this->uow->getCollectionPersister()->update($d, [$d->issues], []);
    }

    public function testDeletingCollectionRespectsVersionNumber()
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

        $this->expectException(LockException::class);
        $this->uow->getCollectionPersister()->delete($d, [$d->issues], []);
    }
}

/** @ODM\MappedSuperclass */
abstract class AbstractVersionBase
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $title;

    /** @ODM\Lock @ODM\Field(type="int") */
    public $locked;

    /** @ODM\EmbedMany(targetDocument=Issue::class) */
    public $issues;

    public function __construct($title = null)
    {
        $this->issues = new ArrayCollection();
        $this->title  = $title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getVersion()
    {
        return $this->version;
    }
}

/** @ODM\Document */
class LockInt extends AbstractVersionBase
{
    /** @ODM\Version @ODM\Field(type="int") */
    public $version;
}

/** @ODM\Document */
class LockDate extends AbstractVersionBase
{
    /** @ODM\Version @ODM\Field(type="date") */
    public $version;
}

/** @ODM\Document */
class LockDateImmutable extends AbstractVersionBase
{
    /** @ODM\Version @ODM\Field(type="date_immutable") */
    public $version;
}

/** @ODM\Document */
class InvalidLockDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Lock @ODM\Field(type="string") */
    public $lock;
}

/** @ODM\Document */
class InvalidVersionDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Version @ODM\Field(type="string") */
    public $version;
}
