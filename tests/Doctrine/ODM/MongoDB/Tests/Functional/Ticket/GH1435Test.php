<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH1435Test extends BaseTestCase
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

#[ODM\Document]
class GH1435Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;
}

#[ODM\Document]
class GH1435DocumentIncrement
{
    /** @var int|null */
    #[ODM\Id(strategy: 'increment')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;
}
