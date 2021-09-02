<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class LifecycleTest extends BaseTest
{
    public function testEventOnDoubleFlush(): void
    {
        $parent = new ParentObject('parent', new ChildObject('child'), new ChildEmbeddedObject('child embedded'));
        $this->dm->persist($parent);
        $this->dm->flush();

        $this->assertCount(1, $parent->getChildren());

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->flush();

        $this->assertCount(1, $parent->getChildren());

        $this->dm->clear();

        $parent = $this->dm->getRepository(ParentObject::class)->find($parent->getId());
        $this->assertNotNull($parent);
        $this->assertEquals('parent #changed', $parent->getName());
        $this->assertCount(1, $parent->getChildren());
        $this->assertEquals('changed', $parent->getChildEmbedded()->getName());
    }

    public function testEventEmptyFlush(): void
    {
        $parent = new ParentObject('parent', new ChildObject('child'), new ChildEmbeddedObject('child embedded'));

        $this->dm->persist($parent);
        $this->dm->flush();

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->getRepository(ParentObject::class)->find($parent->getId());
        $this->assertNotNull($parent);
        $this->assertCount(1, $parent->getChildren());
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class ParentObject
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceMany(targetDocument=ChildObject::class, cascade="all") */
    private $children;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /** @ODM\EmbedOne(targetDocument=ChildEmbeddedObject::class) */
    private $childEmbedded;

    private $child;

    public function __construct($name, ChildObject $child, ChildEmbeddedObject $childEmbedded)
    {
        $this->name          = $name;
        $this->child         = $child;
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
    public function prePersistPreUpdate(): void
    {
        $this->children = [$this->child];
    }

    /** @ODM\PreUpdate */
    public function preUpdate(): void
    {
        $this->childEmbedded->setName('changed');
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getChildEmbedded(): ChildEmbeddedObject
    {
        return $this->childEmbedded;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class ChildObject
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
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
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
