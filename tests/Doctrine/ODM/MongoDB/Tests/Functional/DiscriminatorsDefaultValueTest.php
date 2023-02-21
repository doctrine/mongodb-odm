<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DiscriminatorsDefaultValueTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * This test demonstrates a document without discriminator being treated with defaultDiscriminatorValue
     */
    public function testLoadDocumentWithDefaultValue(): void
    {
        // Create referenced document without discriminator value
        $this->dm->persist($firstChildWithoutDiscriminator  = new ChildDocumentWithoutDiscriminator('firstWithoutDiscriminator'));
        $this->dm->persist($secondChildWithoutDiscriminator = new ChildDocumentWithoutDiscriminator('firstWithoutDiscriminator'));

        $children = [$firstChildWithoutDiscriminator, $secondChildWithoutDiscriminator];
        $this->dm->persist($parentWithoutDiscriminator = new ParentDocumentWithoutDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $childWithDiscriminator = $this->dm->find(ChildDocumentWithDiscriminator::class, $firstChildWithoutDiscriminator->getId());
        self::assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $childWithDiscriminator);
        self::assertSame('firstWithoutDiscriminator', $childWithDiscriminator->getType(), 'New mapping correctly loads legacy document');

        $parentWithDiscriminator = $this->dm->find(ParentDocumentWithDiscriminator::class, $parentWithoutDiscriminator->getId());
        self::assertNotNull($parentWithDiscriminator);
        self::assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getReferencedChild(), 'Referenced document correctly respects defaultDiscriminatorValue in referenceOne mapping');
        self::assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document correctly respects defaultDiscriminatorValue in referenceOne mapping');
        self::assertContainsOnlyInstancesOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getReferencedChildren(), 'Referenced document correctly respects defaultDiscriminatorValue in referenceMany mapping');
        self::assertContainsOnlyInstancesOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getEmbeddedChildren(), 'Embedded document correctly respects defaultDiscriminatorValue in referenceMany mapping');
    }

    /**
     * This test ensures that a discriminatorValue is stored in the database and overrides the default
     */
    public function testLoadDocumentWithDifferentChild(): void
    {
        $this->dm->persist($firstChildWithDiscriminator  = new ChildDocumentWithDiscriminatorComplex('firstWithDiscriminator', 'veryComplex'));
        $this->dm->persist($secondChildWithDiscriminator = new ChildDocumentWithDiscriminatorSimple('secondWithDiscriminator'));

        $children = [$firstChildWithDiscriminator, $secondChildWithDiscriminator];
        $this->dm->persist($parentWithDiscriminator = new ParentDocumentWithDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $parentWithDiscriminator = $this->dm->find(ParentDocumentWithDiscriminator::class, $parentWithDiscriminator->getId());
        self::assertNotNull($parentWithDiscriminator);
        self::assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $parentWithDiscriminator->getReferencedChild(), 'Referenced document respects discriminatorValue if it is present in referenceOne mapping');
        self::assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document respects discriminatorValue if it is present in referenceOne mapping');

        // Check referenceMany mapping
        $referencedChildren = $parentWithDiscriminator->getReferencedChildren()->toArray();
        self::assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        self::assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        self::assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        self::assertSame('secondWithDiscriminator', $referencedChildren[1]->getType());

        // Check embedMany mapping
        $referencedChildren = $parentWithDiscriminator->getEmbeddedChildren()->toArray();
        self::assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        self::assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        self::assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        self::assertSame('secondWithDiscriminator', $referencedChildren[1]->getType());
    }
}

// Unmapped superclasses
/** @ODM\Document(collection="discriminator_parent") */
abstract class ParentDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /** @var ChildDocument */
    protected $referencedChild;

    /** @var Collection<int, ChildDocument>|array<ChildDocument> */
    protected $referencedChildren;

    /** @var ChildDocument */
    protected $embeddedChild;

    /** @var Collection<int, ChildDocument>|array<ChildDocument> */
    protected $embeddedChildren;

    /** @param array{0: ChildDocument, 1: ChildDocument} $children */
    public function __construct(array $children)
    {
        $this->referencedChild    = $children[0];
        $this->referencedChildren = $children;
        $this->embeddedChild      = $children[0];
        $this->embeddedChildren   = $children;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReferencedChild(): ChildDocument
    {
        return $this->referencedChild;
    }

    /** @return Collection<int, ChildDocument>|array<ChildDocument> */
    public function getReferencedChildren()
    {
        return $this->referencedChildren;
    }

    public function getEmbeddedChild(): ChildDocument
    {
        return $this->embeddedChild;
    }

    /** @return Collection<int, ChildDocument>|array<ChildDocument> */
    public function getEmbeddedChildren()
    {
        return $this->embeddedChildren;
    }
}

/** @ODM\Document(collection="discriminator_child") */
abstract class ChildDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}

// Documents without discriminators - used to create "legacy" data
/** @ODM\Document(collection="discriminator_parent") */
class ParentDocumentWithoutDiscriminator extends ParentDocument
{
    /**
     * @ODM\ReferenceOne(targetDocument=ChildDocumentWithoutDiscriminator::class)
     *
     * @var ChildDocumentWithDiscriminator
     */
    protected $referencedChild;

    /**
     * @ODM\ReferenceMany(targetDocument=ChildDocumentWithoutDiscriminator::class)
     *
     * @var Collection<int, ChildDocumentWithDiscriminator>|array<ChildDocumentWithDiscriminator>
     */
    protected $referencedChildren;

    /**
     * @ODM\EmbedOne(targetDocument=ChildDocumentWithoutDiscriminator::class)
     *
     * @var ChildDocumentWithoutDiscriminator
     */
    protected $embeddedChild;

    /**
     * @ODM\EmbedMany(targetDocument=ChildDocumentWithoutDiscriminator::class)
     *
     * @var Collection<int, ChildDocumentWithDiscriminator>|array<ChildDocumentWithDiscriminator>
     */
    protected $embeddedChildren;
}

/** @ODM\Document(collection="discriminator_child") */
class ChildDocumentWithoutDiscriminator extends ChildDocument
{
}

// Documents with discriminators - these represent a "refactored" document structure
/** @ODM\Document(collection="discriminator_parent") */
class ParentDocumentWithDiscriminator extends ParentDocument
{
    /**
     * @ODM\ReferenceOne(targetDocument=ChildDocumentWithDiscriminator::class)
     *
     * @var ChildDocumentWithDiscriminator
     */
    protected $referencedChild;

    /**
     * @ODM\ReferenceMany(targetDocument=ChildDocumentWithDiscriminator::class)
     *
     * @var Collection<int, ChildDocumentWithDiscriminator>|array<ChildDocumentWithDiscriminator>
     */
    protected $referencedChildren;

    /**
     * @ODM\EmbedOne(targetDocument=ChildDocumentWithDiscriminator::class)
     *
     * @var ChildDocumentWithDiscriminator
     */
    protected $embeddedChild;

    /**
     * @ODM\EmbedMany(targetDocument=ChildDocumentWithDiscriminator::class)
     *
     * @var Collection<int, ChildDocumentWithDiscriminator>
     */
    protected $embeddedChildren;
}

/**
 * @ODM\Document(collection="discriminator_child")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("discriminator")
 * @ODM\DiscriminatorMap({"simple"=ChildDocumentWithDiscriminatorSimple::class, "complex"=ChildDocumentWithDiscriminatorComplex::class})
 * @ODM\DefaultDiscriminatorValue("simple")
 */
class ChildDocumentWithDiscriminator extends ChildDocument
{
}

/** @ODM\Document(collection="discriminator_child") */
class ChildDocumentWithDiscriminatorSimple extends ChildDocumentWithDiscriminator
{
}

/** @ODM\Document(collection="discriminator_child") */
class ChildDocumentWithDiscriminatorComplex extends ChildDocumentWithDiscriminatorSimple
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $value;

    public function __construct(string $type, string $value)
    {
        parent::__construct($type);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
