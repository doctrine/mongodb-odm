<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

class LifecycleTest extends BaseTest
{

    public function testEvent()
    {
        $parent = new ParentObject('parent', new ChildObject('child'));

        $this->dm->persist($parent);

        unset($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->findOne(__NAMESPACE__.'\ParentObject');
        $this->assertEquals(1, count($parent->getChildren()));
        $this->dm->flush();
        $this->assertEquals(1, count($parent->getChildren()));
    }

}

/** @Document */
class ParentObject
{
    /** @Id */
    private $id;

    /** @ReferenceMany(targetDocument="ChildObject") */
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

    /** @PrePresist @PreUpdate */
    public function updateChildrenCollection()
    {
        $this->children = array($this->child);
    }

    public function getChildren()
    {
        return $this->children;
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