<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH1435Test extends BaseTest
{
    public function testUpsert(): void
    {
        $id = (string) new ObjectId();

        $document       = new GH1435Document();
        $document->id   = $id;
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH1435Document::class, $id);
        self::assertNotNull($document);
        self::assertEquals('test', $document->name);
    }

    public function testUpsertWithIncrement(): void
    {
        $id = 10;

        $document       = new GH1435DocumentIncrement();
        $document->id   = $id;
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH1435DocumentIncrement::class, $id);
        self::assertNotNull($document);
        self::assertEquals('test', $document->name);
    }

    public function testUpdateWithIncrement(): void
    {
        $document       = new GH1435DocumentIncrement();
        $document->name = 'test';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1435DocumentIncrement::class)->findOneBy([]);
        self::assertNotNull($document);
        self::assertEquals('test', $document->name);

        $document->id += 5;
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1435DocumentIncrement::class)->findOneBy([]);
        self::assertNotNull($document);
        self::assertSame(1, $document->id);
    }
}

/** @ODM\Document() */
class GH1435Document
{
    /**
     * @ODM\Id()
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\Document() */
class GH1435DocumentIncrement
{
    /**
     * @ODM\Id(strategy="increment")
     *
     * @var int|null
     */
    public $id;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @var string|null
     */
    public $name;
}
