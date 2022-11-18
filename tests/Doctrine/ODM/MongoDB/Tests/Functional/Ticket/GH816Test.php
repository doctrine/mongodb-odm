<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH816Test extends BaseTest
{
    public function testPersistAfterDetachWithIdSet(): void
    {
        $d     = new GH816Document();
        $d->id = new ObjectId();
        self::assertEmpty($this->dm->getRepository(GH816Document::class)->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        self::assertEmpty($this->dm->getRepository(GH816Document::class)->findAll());
    }

    public function testPersistAfterDetachWithTitleSet(): void
    {
        $d        = new GH816Document();
        $d->title = 'Test';
        self::assertEmpty($this->dm->getRepository(GH816Document::class)->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        self::assertEmpty($this->dm->getRepository(GH816Document::class)->findAll());
    }
}

/** @ODM\Document */
class GH816Document
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;
}
