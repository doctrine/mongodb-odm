<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

class LifecycleTest extends BaseTest
{
    public function testEvent()
    {
        $parent = new ParentObject('parent', new ChildObject('child'));
        $this->dm->persist($parent);
        $this->dm->flush();

        $this->assertEquals(1, count($parent->getChildren()));

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->flush();

        $this->assertEquals(1, count($parent->getChildren()));

        unset($parent);
        $this->dm->clear();

        $parent = $this->dm->findOne(__NAMESPACE__.'\ParentObject');
        $this->assertEquals('parent #changed', $parent->getName());
        $this->assertEquals(1, count($parent->getChildren()));
    }
}

/** @Document @HasLifecycleCallbacks */
class ParentObject
{
    /** @Id */
    private $id;

    /** @ReferenceMany(targetDocument="ChildObject", cascade="all") */
    private $children;

    /** @String */
    private $name;

    private $child;

    public function __construct($name, ChildObject $child)
    {
        $this->name = $name;
        $this->child = $child;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /** @PrePersist @PreUpdate */
    public function updateChildrenCollection()
    {
        $this->children = array($this->child);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/** @Document */
class ChildObject
{
    /** @Id */
    private $id;

    /** @String */
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