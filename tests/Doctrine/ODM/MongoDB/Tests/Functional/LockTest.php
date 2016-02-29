<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Issue;

class LockTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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
        $id = new \MongoId();

        $article = new LockInt('Test LockInt');
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
        $this->dm->getDocumentCollection(get_class($article))->update(array('_id' => new \MongoId($article->id)), array('$set' => array('version' => 5)));

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->flush();
    }

    public function testMultipleFlushesDoIncrementalUpdates()
    {
        $test = new LockInt();

        for ($i = 0; $i < 5; $i++) {
            $test->title = 'test' . $i;
            $this->dm->persist($test);
            $this->dm->flush();

            $this->assertInternalType('int', $test->getVersion());
            $this->assertEquals($i + 1, $test->getVersion());
        }
    }

    public function testLockTimestampSetsDefaultValue()
    {
        $test = new LockTimestamp();
        $test->title = 'Testing';

        $this->assertNull($test->version, "Pre-Condition");

        $this->dm->persist($test);
        $this->dm->flush();

        $date1 = $test->version;

        $this->assertInstanceOf('DateTime', $date1);

        $test->title = 'changed';
        $this->dm->flush();

        $this->assertNotSame($date1, $test->version);

        return $test;
    }

    public function testLockTimestampSetsDefaultValueOnUpsert()
    {
        $id = new \MongoId();

        $test = new LockTimestamp();
        $test->title = 'Testing';
        $test->id = $id;

        $this->assertNull($test->version, "Pre-Condition");

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

    public function testLockTimestampThrowsException()
    {
        $article = new LockTimestamp('Test LockInt');
        $this->dm->persist($article);
        $this->dm->flush();

        // Manually change the version so the next code will cause an exception
        $this->dm->getDocumentCollection(get_class($article))->update(array('_id' => new \MongoId($article->id)), array('$set' => array('version' => new \MongoDate(time() + 600))));

        // Now lets change a property and try and save it again
        $article->title = 'ok';

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->flush();
    }

    public function testLockVersionedDocument()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version);
    }

    public function testLockVersionedDocumentMissmatchThrowsException()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockUnversionedDocumentThrowsException()
    {
        $user = new \Documents\User();
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException', 'Document Documents\User is not versioned.');

        $this->dm->lock($user, LockMode::OPTIMISTIC);
    }

    public function testLockUnmanagedDocumentThrowsException()
    {
        $article = new LockInt();

        $this->setExpectedException('InvalidArgumentException', 'Document is not MANAGED.');

        $this->dm->lock($article, LockMode::OPTIMISTIC, $article->version + 1);
    }

    public function testLockPessimisticWrite()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_WRITE);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_WRITE, $check['locked']);
    }

    public function testLockPessimisticRead()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $check['locked']);
    }

    public function testUnlock()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $check['locked']);
        $this->assertEquals(LockMode::PESSIMISTIC_READ, $article->locked);

        $this->dm->unlock($article);

        $check = $this->dm->getDocumentCollection(get_class($article))->findOne();
        $this->assertFalse(isset($check['locked']));
        $this->assertNull($article->locked);
    }

    public function testPessimisticReadLockThrowsExceptionOnRemove()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt');
        $coll->update(array('_id' => new \MongoId($article->id)), array('locked' => LockMode::PESSIMISTIC_READ));

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->remove($article);
        $this->dm->flush();
    }

    public function testPessimisticReadLockThrowsExceptionOnUpdate()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt');
        $coll->update(array('_id' => new \MongoId($article->id)), array('locked' => LockMode::PESSIMISTIC_READ));

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $article->title = 'changed';
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnRemove()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt');
        $coll->update(array('_id' => new \MongoId($article->id)), array('locked' => LockMode::PESSIMISTIC_WRITE));

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->remove($article);
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnUpdate()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt');
        $coll->update(array('_id' => new \MongoId($article->id)), array('locked' => LockMode::PESSIMISTIC_WRITE));

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $article->title = 'changed';
        $this->dm->flush();
    }

    public function testPessimisticWriteLockThrowExceptionOnRead()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt');
        $coll->update(array('_id' => new \MongoId($article->id)), array('locked' => LockMode::PESSIMISTIC_WRITE));

        $this->setExpectedException('Doctrine\ODM\MongoDB\LockException');

        $this->dm->clear();
        $article = $this->dm->find(__NAMESPACE__.'\LockInt', $article->id);
    }

    public function testPessimisticReadLockFunctional()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_READ);

        $article->title = 'test';
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt')->findOne();
        $this->assertEquals(2, $check['version']);
        $this->assertFalse(isset($check['locked']));
        $this->assertEquals('test', $check['title']);
    }

    public function testPessimisticWriteLockFunctional()
    {
        $article = new LockInt();
        $article->title = "my article";

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->lock($article, LockMode::PESSIMISTIC_WRITE);

        $article->title = 'test';
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt')->findOne();
        $this->assertEquals(2, $check['version']);
        $this->assertFalse(isset($check['locked']));
        $this->assertEquals('test', $check['title']);
    }

    public function testInvalidLockDocument()
    {
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException', 'Invalid lock field type string. Lock field must be int.');
        $this->dm->getClassMetadata(__NAMESPACE__.'\InvalidLockDocument');
    }

    public function testInvalidVersionDocument()
    {
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException', 'Invalid version field type string. Version field must be int or date.');
        $this->dm->getClassMetadata(__NAMESPACE__.'\InvalidVersionDocument');
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\LockException
     */
    public function testUpdatingCollectionRespectsVersionNumber()
    {
        $d = new LockInt('test');
        $d->issues->add(new Issue('hi', 'ohai'));
        $this->dm->persist($d);
        $this->dm->flush();

        // simulate another request updating document in the meantime
        $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt')->update(
            array('_id' => new \MongoId($d->id)),
            array('$set' => array('version' => 2))
        );

        $d->issues->add(new Issue('oops', 'version mismatch'));
        $this->uow->getCollectionPersister()->update($d->issues, array());
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\LockException
     */
    public function testDeletingCollectionRespectsVersionNumber()
    {
        $d = new LockInt('test');
        $d->issues->add(new Issue('hi', 'ohai'));
        $this->dm->persist($d);
        $this->dm->flush();

        // simulate another request updating document in the meantime
        $this->dm->getDocumentCollection(__NAMESPACE__.'\LockInt')->update(
            array('_id' => new \MongoId($d->id)),
            array('$set' => array('version' => 2))
        );

        $this->uow->getCollectionPersister()->delete($d->issues, array());
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

    /**
     * @ODM\EmbedMany(targetDocument="Documents\Issue")
     */
    public $issues;

    public function __construct($title = null)
    {
        $this->issues = new ArrayCollection();
        $this->title = $title;
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
class LockTimestamp extends AbstractVersionBase
{
    /** @ODM\Version @ODM\Field(type="date") */
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
