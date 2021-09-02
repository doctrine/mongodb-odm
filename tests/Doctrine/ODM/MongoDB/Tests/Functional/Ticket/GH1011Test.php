<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1011Test extends BaseTest
{
    public function testClearCollection(): void
    {
        $doc = new GH1011Document();
        $doc->embeds->add(new GH1011Embedded('test1'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $doc->embeds->clear();
        $doc->embeds->add(new GH1011Embedded('test2'));
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(PersistentCollectionInterface::class, $doc->embeds);
        $this->assertTrue($this->uow->isCollectionScheduledForUpdate($doc->embeds));
        $this->assertFalse($this->uow->isCollectionScheduledForDeletion($doc->embeds));
    }

    public function testReplaceCollection(): void
    {
        $doc = new GH1011Document();
        $doc->embeds->add(new GH1011Embedded('test1'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $oldCollection = $doc->embeds;
        $doc->embeds   = new ArrayCollection();
        $doc->embeds->add(new GH1011Embedded('test2'));
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(PersistentCollectionInterface::class, $doc->embeds);
        $this->assertTrue($this->uow->isCollectionScheduledForUpdate($doc->embeds));
        $this->assertInstanceOf(PersistentCollectionInterface::class, $oldCollection);
        $this->assertFalse($this->uow->isCollectionScheduledForDeletion($oldCollection));
    }
}

/** @ODM\Document */
class GH1011Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument=GH1011Embedded::class, strategy="set")
     *
     * @var Collection<int, GH1011Embedded>
     */
    public $embeds;

    public function __construct()
    {
        $this->embeds = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1011Embedded
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
