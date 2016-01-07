<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1229Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var string
     */
    protected $firstParentId;

    /**
     * @var string
     */
    protected $secondParentId;

    /**
     * {@inheritdoc}
     */
    public function setUp()
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

        $this->firstParentId = $firstParent->id;
        $this->secondParentId = $secondParent->id;
    }

    /**
     * @group m
     */
    public function testMethodAWithoutClone()
    {
        /** @var GH1229Parent $firstParent */
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        $this->assertNotNull($firstParent);

        /** @var GH1229Parent $secondParent */
        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        $this->assertNotNull($secondParent);

        foreach ($firstParent->getChildren() as $child) {
            if ($child->getOrder() !== 0) {
                continue;
            }

            $firstParent->removeChild($child);
            $secondParent->addChild($child);

            $this->dm->flush();
            $actualChildren = $secondParent->getChildren();

            $this->assertNotSame($actualChildren, $child);

            list(, $parent, ) = $this->uow->getParentAssociation(end($actualChildren));
            $this->assertSame($this->secondParentId, $parent->id);
        }

        $this->dm->clear();

        /** @var GH1229Parent $firstParent */
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        $this->assertNotNull($firstParent);
        $this->assertCount(0, $firstParent->getChildren());

        /** @var GH1229Parent $secondParent */
        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        $this->assertNotNull($secondParent);
        $this->assertCount(2, $secondParent->getChildren());

        $children = $secondParent->getChildren();

        $this->assertInstanceOf(GH1229Child::CLASSNAME, $children[0]);
        $this->assertInstanceOf(GH1229ChildTypeB::CLASSNAME, $children[1]);
    }

    /**
     * @group m
     */
    public function testMethodAWithClone()
    {
        /** @var GH1229Parent $firstParent */
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        $this->assertNotNull($firstParent);

        /** @var GH1229Parent $secondParent */
        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        $this->assertNotNull($secondParent);

        foreach ($firstParent->getChildren() as $child) {
            if ($child->getOrder() !== 0) {
                continue;
            }

            $firstParent->removeChild($child);
            $secondParent->addChild(clone $child);

            $this->dm->flush();
        }

        $this->dm->clear();

        /** @var GH1229Parent $firstParent */
        $firstParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->firstParentId);
        $this->assertNotNull($firstParent);
        $this->assertCount(0, $firstParent->getChildren());

        /** @var GH1229Parent $secondParent */
        $secondParent = $this->dm->find(GH1229Parent::CLASSNAME, $this->secondParentId);
        $this->assertNotNull($secondParent);
        $this->assertCount(2, $secondParent->getChildren());

        $children = $secondParent->getChildren();

        $this->assertInstanceOf(GH1229Child::CLASSNAME, $children[0]);
        $this->assertInstanceOf(GH1229ChildTypeB::CLASSNAME, $children[1]);
    }
}

/** @ODM\Document */
class GH1229Parent
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(discriminatorField="_class") */
    protected $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return GH1229Child[]
     */
    public function getChildren()
    {
        return $this->children->toArray();
    }

    /**
     * @param GH1229Child $child
     */
    public function addChild(GH1229Child $child)
    {
        $child->setOrder(count($this->children));
        $this->children->add($child);
    }

    /**
     * @param GH1229Child $child
     */
    public function removeChild(GH1229Child $child)
    {
        $this->children->removeElement($child);
        $this->reorderChildren($child->getOrder(), -1);
    }

    /**
     * @param int $starting
     * @param int $change
     */
    public function reorderChildren($starting, $change)
    {
        foreach ($this->children as $child) {
            if ($child->getOrder() >= $starting) {
                $child->setOrder($child->getOrder() + $change);
            }
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH1229Child
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="int") */
    public $order = 0;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }
}

/** @ODM\EmbeddedDocument */
class GH1229ChildTypeB extends GH1229Child
{
    const CLASSNAME = __CLASS__;
}
