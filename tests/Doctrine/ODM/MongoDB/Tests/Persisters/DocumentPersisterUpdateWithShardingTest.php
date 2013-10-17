<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;

class DocumentPersisterUpdateWithShardingTest extends BaseTest
{
    private $pb;

    public function setUp()
    {
        parent::setUp();
        $this->pb = $this->dm->getUnitOfWork()->getPersistenceBuilder();

    }

    public function tearDown()
    {
        unset($this->pb);
        parent::tearDown();
    }

    public function testGetShardKeyQuery()
    {
        $post = new PostShard();
        $this->assertEquals(array('_id' => $post->id, 'key' => $post->getKey()), $this->uow->getDocumentPersister(get_class($post))->getShardKeyQuery($post));
    }

    public function testUpdateAfterSave()
    {
        $post = new PostShard();
        $post->title = 'test';
        $post->setKey('testing');
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(get_class($post), $post->id);
        $this->assertEquals($post->title, $post->title);

        $post->title = 'test2';

        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(get_class($post), $post->id);
        $this->assertEquals($post->title, $post->title);
        $this->assertEquals($post->getKey(), $post->getKey());
        $this->assertEquals(array('_id' => $post->id, 'key' => $post->getKey()), $this->uow->getDocumentPersister(get_class($post))->getShardKeyQuery($post));
    }

    public function testUpsert()
    {
        $post = new PostShard();
        $post->id = new \MongoId();
        $post->title = 'test';
        $post->setKey('testing');
        $this->dm->persist($post);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testUpdateWithShardKeyChangeException()
    {
        $post = new PostShard();
        $post->title = 'test';
        $post->setKey('testing');
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(get_class($post), $post->id);
        $this->assertEquals($post->title, $post->title);

        $post->title = 'test2';
        $post->setKey('testing2');

        $this->dm->flush();
    }
}

/** @ODM\Document */
class PostShard
{
    /** @ODM\Id(options={"isShardKey"=true},strategy="Auto") */
    public $id;

    /** @ODM\String */
    public $title;

    /**
     * @ODM\String(name="key",options={"isShardKey"="true"})
     */
    private $fullKeyName = 'shardKey';

    public function __construct()
    {
        $this->id = new \MongoId();
    }

    public function setKey($key)
    {
        $this->fullKeyName = $key;
    }

    public function getKey()
    {
        return $this->fullKeyName;
    }
}