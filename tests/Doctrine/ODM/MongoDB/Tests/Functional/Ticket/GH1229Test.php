<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;
use function count;
use function end;

class GH1229Test extends BaseTest
{
    /** @var string */
    protected $firstParentId;

    /** @var string */
    protected $secondParentId;

    public function setUp(): void
    {
        parent::setUp();

        $firstParent = new GH1229Parent();
        $this->dm->persist($firstParent);

        $secondParent = new GH1229Parent();
        $this->dm->persist($secondParent);

        $firstParent->addChild(new GH1229Child('type a'));
        $firstParent->addChild(new GH1229ChildTypeB('type b'));

        $this->dm->flush();
        $this->dm->clear();

        $this->firstParentId  = $firstParent->id;
        $this->secondParentId = $secondParent->id;
    }

    /** @group m */
    public function testMethodAWithoutClone(): void
    {
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        assert($firstParent instanceof GH1229Parent);
        self::assertNotNull($firstParent);

        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        assert($secondParent instanceof GH1229Parent);
        self::assertNotNull($secondParent);

        foreach ($firstParent->getChildren() as $child) {
            if ($child->getOrder() !== 0) {
                continue;
            }

            $firstParent->removeChild($child);
            $secondParent->addChild($child);

            $this->dm->flush();
            $actualChildren = $secondParent->getChildren();

            self::assertNotSame($actualChildren, $child);

            [, $parent] = $this->uow->getParentAssociation(end($actualChildren));
            self::assertSame($this->secondParentId, $parent->id);
        }

        $this->dm->clear();

        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        assert($firstParent instanceof GH1229Parent);
        self::assertNotNull($firstParent);
        self::assertEmpty($firstParent->getChildren());

        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        assert($secondParent instanceof GH1229Parent);
        self::assertNotNull($secondParent);
        self::assertCount(2, $secondParent->getChildren());

        $children = $secondParent->getChildren();

        self::assertInstanceOf(GH1229Child::CLASSNAME, $children[0]);
        self::assertInstanceOf(GH1229ChildTypeB::CLASSNAME, $children[1]);
    }

    /** @group m */
    public function testMethodAWithClone(): void
    {
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        assert($firstParent instanceof GH1229Parent);
        self::assertNotNull($firstParent);

        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        assert($secondParent instanceof GH1229Parent);
        self::assertNotNull($secondParent);

        foreach ($firstParent->getChildren() as $child) {
            if ($child->getOrder() !== 0) {
                continue;
            }

            $firstParent->removeChild($child);
            $secondParent->addChild(clone $child);

            $this->dm->flush();
        }

        $this->dm->clear();

        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        assert($firstParent instanceof GH1229Parent);
        self::assertNotNull($firstParent);
        self::assertEmpty($firstParent->getChildren());

        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        assert($secondParent instanceof GH1229Parent);
        self::assertNotNull($secondParent);
        self::assertCount(2, $secondParent->getChildren());

        $children = $secondParent->getChildren();

        self::assertInstanceOf(GH1229Child::CLASSNAME, $children[0]);
        self::assertInstanceOf(GH1229ChildTypeB::CLASSNAME, $children[1]);
    }
}

/** @ODM\Document */
class GH1229Parent
{
    public const CLASSNAME = self::class;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(discriminatorField="_class")
     *
     * @var Collection<int, GH1229Child>
     */
    protected $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /** @return GH1229Child[] */
    public function getChildren(): array
    {
        return $this->children->toArray();
    }

    public function addChild(GH1229Child $child): void
    {
        $child->setOrder(count($this->children));
        $this->children->add($child);
    }

    public function removeChild(GH1229Child $child): void
    {
        $this->children->removeElement($child);
        $this->reorderChildren($child->getOrder(), -1);
    }

    public function reorderChildren(int $starting, int $change): void
    {
        foreach ($this->children as $child) {
            if ($child->getOrder() < $starting) {
                continue;
            }

            $child->setOrder($child->getOrder() + $change);
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH1229Child
{
    public const CLASSNAME = self::class;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="int")
     *
     * @var int
     */
    public $order = 0;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /** @return $this */
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }
}

/** @ODM\EmbeddedDocument */
class GH1229ChildTypeB extends GH1229Child
{
    public const CLASSNAME = self::class;
}
