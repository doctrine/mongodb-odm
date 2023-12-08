<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class LifecycleTest extends BaseTestCase
{
    public function testEventOnDoubleFlush(): void
    {
        $parent = new ParentObject('parent', new ChildObject('child'), new ChildEmbeddedObject('child embedded'));
        $this->dm->persist($parent);
        $this->dm->flush();

        self::assertCount(1, $parent->getChildren());

        $parent->setName('parent #changed');

        $this->dm->flush();
        $this->dm->flush();

        self::assertCount(1, $parent->getChildren());

        $this->dm->clear();

        $parent = $this->dm->getRepository(ParentObject::class)->find($parent->getId());
        self::assertNotNull($parent);
        self::assertEquals('parent #changed', $parent->getName());
        self::assertCount(1, $parent->getChildren());
        self::assertEquals('changed', $parent->getChildEmbedded()->getName());
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
        self::assertNotNull($parent);
        self::assertCount(1, $parent->getChildren());
    }
}

#[ODM\Document]
#[ODM\HasLifecycleCallbacks]
class ParentObject
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var Collection<int, ChildObject>|array<ChildObject> */
    #[ODM\ReferenceMany(targetDocument: ChildObject::class, cascade: 'all')]
    private $children;

    /** @var string */
    #[ODM\Field(type: 'string')]
    private $name;

    /** @var ChildEmbeddedObject */
    #[ODM\EmbedOne(targetDocument: ChildEmbeddedObject::class)]
    private $childEmbedded;

    /** @var ChildObject */
    private $child;

    public function __construct(string $name, ChildObject $child, ChildEmbeddedObject $childEmbedded)
    {
        $this->name          = $name;
        $this->child         = $child;
        $this->childEmbedded = $childEmbedded;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    #[ODM\PrePersist]
    #[ODM\PreUpdate]
    public function prePersistPreUpdate(): void
    {
        $this->children = [$this->child];
    }

    #[ODM\PreUpdate]
    public function preUpdate(): void
    {
        $this->childEmbedded->setName('changed');
    }

    /** @return Collection<int, ChildObject>|array<ChildObject> */
    public function getChildren()
    {
        return $this->children;
    }

    public function getChildEmbedded(): ChildEmbeddedObject
    {
        return $this->childEmbedded;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}

#[ODM\Document]
class ChildObject
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

#[ODM\EmbeddedDocument]
class ChildEmbeddedObject
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
