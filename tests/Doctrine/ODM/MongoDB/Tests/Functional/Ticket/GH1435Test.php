<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;

class GH1435Test extends BaseTest
{
    public function testUpsert()
    {
        $id = (string) new \MongoId();

        $document = new GH1435Document();
        $document->id = $id;
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH1435Document::class, $id);
        $this->assertNotNull($document);
        $this->assertEquals('test', $document->name);
    }

    public function testUpsertWithIncrement()
    {
        $id = 10;

        $document = new GH1435DocumentIncrement();
        $document->id = $id;
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH1435DocumentIncrement::class, $id);
        $this->assertNotNull($document);
        $this->assertEquals('test', $document->name);
    }

    public function testUpdateWithIncrement()
    {
        $document = new GH1435DocumentIncrement();
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush($document);
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1435DocumentIncrement::class)->findOneBy([]);
        $this->assertNotNull($document);
        $this->assertEquals('test', $document->name);

        $document->id += 5;
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1435DocumentIncrement::class)->findOneBy([]);
        $this->assertNotNull($document);
        $this->assertSame(1, $document->id);
    }

    protected function createMetadataDriverImpl()
    {
        return new YamlDriver(__DIR__ . '/GH1435');
    }
}

class GH1435Document
{
    public $id;

    public $name;
}

class GH1435DocumentIncrement
{
    public $id;

    public $name;
}
