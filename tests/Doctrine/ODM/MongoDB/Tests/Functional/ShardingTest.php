<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ShardingTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        /** @var SchemaManager $schemaManager */
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->ensureDocumentSharding(__NAMESPACE__ . '\ShardedOne');
    }

    /**
     * @group sharding
     */
    public function testUpdateAfterSave()
    {
        $o = new ShardedOne();
        $o->title = 'test';
        $o->key = 'testing';
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->clear();

        /** @var ShardedOne $o */
        $o = $this->dm->find(get_class($o), $o->id);
        $o->title = 'test2';
        $this->dm->flush();
        $this->dm->clear();

        $o = $this->dm->find(get_class($o), $o->id);

        $this->assertEquals('test2', $o->title);
    }

    /**
     * @group sharding
     */
    public function testUpsert()
    {
        $o = new ShardedOne();
        $o->id = new \MongoId();
        $o->title = 'test';
        $o->key = 'testing';
        $this->dm->persist($o);
        $this->dm->flush();
    }

    /**
     * @group sharding
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testUpdateWithShardKeyChangeException()
    {
        $o = new ShardedOne();
        $o->title = 'test';
        $o->key = 'testing';
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->clear();

        $o = $this->dm->find(get_class($o), $o->id);
        $o->key = 'testing2';

        $this->dm->flush();
    }
}

/**
 * @ODM\Document
 * @ODM\ShardKey(fields={"key"="asc"})
 */
class ShardedOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $title;

    /** @ODM\String(name="k") */
    public $key;
}