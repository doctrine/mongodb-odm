<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;

class GH774Test extends BaseTest
{
    public function testUpsert()
    {
        $id = (string) new \MongoId();

        $thread = new GH774Thread();
        $thread->id = $id;
        $thread->permalink = 'test';

        $this->dm->persist($thread);
        $this->dm->flush();
        $this->dm->clear();

        $thread = $this->dm->find(get_class($thread), $id);
        $this->assertNotNull($thread);
        $this->assertEquals('test', $thread->permalink);
    }

    public function testUpsertSingleFlush()
    {
        $id = (string) new \MongoId();

        $thread = new GH774Thread();
        $thread->id = $id;
        $thread->permalink = 'test';

        $this->dm->persist($thread);
        $this->dm->flush($thread);
        $this->dm->clear();

        $thread = $this->dm->find(get_class($thread), $id);
        $this->assertNotNull($thread);
        $this->assertEquals('test', $thread->permalink);
    }

    protected function createMetadataDriverImpl()
    {
        return new XmlDriver(__DIR__ . '/GH774');
    }
}

abstract class GH774AbstractThread
{
    public $id;

    public $permalink;
}

class GH774Thread extends GH774AbstractThread
{
}
