<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\UTCDateTime;

class MODM56Test extends BaseTestCase
{
    public function testTest(): void
    {
        $parent = new MODM56Parent('Parent');
        $this->dm->persist($parent);
        $this->dm->flush();

        $childOne           = new MODM56Child('Child One');
        $parent->children[] = $childOne;

        $childTwo           = new MODM56Child('Child Two');
        $parent->children[] = $childTwo;
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(MODM56Parent::class)->findOne();

        self::assertEquals('Parent', $test['name']);
        self::assertInstanceOf(UTCDateTime::class, $test['updatedAt']);
        self::assertCount(2, $test['children']);
        self::assertEquals('Child One', $test['children'][0]['name']);
        self::assertEquals('Child Two', $test['children'][1]['name']);
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class MODM56Parent
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    public $updatedAt;

    /**
     * @ODM\EmbedMany(targetDocument=MODM56Child::class)
     *
     * @var Collection<int, MODM56Child>|array<MODM56Child>
     */
    public $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /** @ODM\PreUpdate */
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}

/** @ODM\EmbeddedDocument */
class MODM56Child
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
