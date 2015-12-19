<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class LifecycleTest extends BaseTest
{
    public function testEventOnDoubleFlush()
    {
        $parent = new ParentObject('parent', new ChildObject('child'), new ChildEmbeddedObject('child embedded'));
        $this->dm->persist($parent);
        $this->dm->flush();

        $this->assertEquals(1, count($parent->getChildren()));

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->flush();

        $this->assertEquals(1, count($parent->getChildren()));

        $this->dm->clear();

        $parent = $this->dm->getRepository(__NAMESPACE__.'\ParentObject')->find($parent->getId());
        $this->assertNotNull($parent);
        $this->assertEquals('parent #changed', $parent->getName());
        $this->assertEquals(1, count($parent->getChildren()));
        $this->assertEquals('changed', $parent->getChildEmbedded()->getName());
    }

    public function testEventEmptyFlush()
    {
        $parent = new ParentObject('parent', new ChildObject('child'), new ChildEmbeddedObject('child embedded'));

        $this->dm->persist($parent);
        $this->dm->flush();

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->getRepository(__NAMESPACE__.'\ParentObject')->find($parent->getId());
        $this->assertNotNull($parent);
        $this->assertEquals(1, count($parent->getChildren()));
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class ParentObject
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceMany(targetDocument="ChildObject", cascade="all") */
    private $children;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\EmbedOne(targetDocument="ChildEmbeddedObject") */
    private $childEmbedded;

    private $child;

    public function __construct($name, ChildObject $child, ChildEmbeddedObject $childEmbedded)
    {
        $this->name = $name;
        $this->child = $child;
        $this->childEmbedded = $childEmbedded;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /** @ODM\PrePersist @ODM\PreUpdate */
    public function prePersistPreUpdate()
    {
        $this->children = array($this->child);
    }

    /** @ODM\PreUpdate */
    public function preUpdate()
    {
        $this->childEmbedded->setName('changed');
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getChildEmbedded()
    {
        return $this->childEmbedded;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class ChildObject
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

/** @ODM\EmbeddedDocument */
class ChildEmbeddedObject
{
    /** @ODM\Field(type="string") */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
