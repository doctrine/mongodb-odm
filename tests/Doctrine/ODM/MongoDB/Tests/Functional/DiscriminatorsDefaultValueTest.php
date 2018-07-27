<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ReferenceDiscriminatorsDefaultValueTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * This test demonstrates a document without discriminator being treated with defaultDiscriminatorValue
     */
    public function testLoadDocumentWithDefaultValue()
    {
        // Create referenced document without discriminator value
        $this->dm->persist($firstChildWithoutDiscriminator = new ChildDocumentWithoutDiscriminator('firstWithoutDiscriminator'));
        $this->dm->persist($secondChildWithoutDiscriminator = new ChildDocumentWithoutDiscriminator('firstWithoutDiscriminator'));

        $children = [$firstChildWithoutDiscriminator, $secondChildWithoutDiscriminator];
        $this->dm->persist($parentWithoutDiscriminator = new ParentDocumentWithoutDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $childWithDiscriminator = $this->dm->find(ChildDocumentWithDiscriminator::class, $firstChildWithoutDiscriminator->getId());
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $childWithDiscriminator);
        $this->assertSame('firstWithoutDiscriminator', $childWithDiscriminator->getType(), 'New mapping correctly loads legacy document');

        $parentWithDiscriminator = $this->dm->find(ParentDocumentWithDiscriminator::class, $parentWithoutDiscriminator->getId());
        $this->assertNotNull($parentWithDiscriminator);
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getReferencedChild(), 'Referenced document correctly respects defaultDiscriminatorValue in referenceOne mapping');
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document correctly respects defaultDiscriminatorValue in referenceOne mapping');

        foreach ($parentWithDiscriminator->getReferencedChildren() as $child) {
            $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $child, 'Referenced document correctly respects defaultDiscriminatorValue in referenceMany mapping');
        }

        foreach ($parentWithDiscriminator->getEmbeddedChildren() as $child) {
            $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $child, 'Embedded document correctly respects defaultDiscriminatorValue in referenceMany mapping');
        }
    }

    /**
     * This test ensures that a discriminatorValue is stored in the database and overrides the default
     */
    public function testLoadDocumentWithDifferentChild()
    {
        $this->dm->persist($firstChildWithDiscriminator = new ChildDocumentWithDiscriminatorComplex('firstWithDiscriminator', 'veryComplex'));
        $this->dm->persist($secondChildWithDiscriminator = new ChildDocumentWithDiscriminatorSimple('secondWithDiscriminator'));

        $children = [$firstChildWithDiscriminator, $secondChildWithDiscriminator];
        $this->dm->persist($parentWithDiscriminator = new ParentDocumentWithDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $parentWithDiscriminator = $this->dm->find(ParentDocumentWithDiscriminator::class, $parentWithDiscriminator->getId());
        $this->assertNotNull($parentWithDiscriminator);
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $parentWithDiscriminator->getReferencedChild(), 'Referenced document respects discriminatorValue if it is present in referenceOne mapping');
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document respects discriminatorValue if it is present in referenceOne mapping');

        // Check referenceMany mapping
        $referencedChildren = $parentWithDiscriminator->getReferencedChildren()->toArray();
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('secondWithDiscriminator', $referencedChildren[1]->getType());

        // Check embedMany mapping
        $referencedChildren = $parentWithDiscriminator->getEmbeddedChildren()->toArray();
        $this->assertInstanceOf(ChildDocumentWithDiscriminatorComplex::class, $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        $this->assertInstanceOf(ChildDocumentWithDiscriminatorSimple::class, $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('secondWithDiscriminator', $referencedChildren[1]->getType());
    }
}

// Unmapped superclasses
/** @ODM\Document(collection="discriminator_parent") */
abstract class ParentDocument
{
    /** @ODM\Id */
    protected $id;

    protected $referencedChild;

    protected $referencedChildren;

    protected $embeddedChild;

    protected $embeddedChildren;

    public function __construct(array $children)
    {
        $this->referencedChild = $children[0];
        $this->referencedChildren = $children;
        $this->embeddedChild = $children[0];
        $this->embeddedChildren = $children;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getReferencedChild()
    {
        return $this->referencedChild;
    }

    public function getReferencedChildren()
    {
        return $this->referencedChildren;
    }

    public function getEmbeddedChild()
    {
        return $this->embeddedChild;
    }

    public function getEmbeddedChildren()
    {
        return $this->embeddedChildren;
    }
}

/** @ODM\Document(collection="discriminator_child") */
abstract class ChildDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }
}

// Documents without discriminators - used to create "legacy" data
/** @ODM\Document(collection="discriminator_parent") */
class ParentDocumentWithoutDiscriminator extends ParentDocument
{
    /** @ODM\ReferenceOne(targetDocument=ChildDocumentWithoutDiscriminator::class) */
    protected $referencedChild;

    /** @ODM\ReferenceMany(targetDocument=ChildDocumentWithoutDiscriminator::class) */
    protected $referencedChildren;

    /** @ODM\EmbedOne(targetDocument=ChildDocumentWithoutDiscriminator::class) */
    protected $embeddedChild;

    /** @ODM\EmbedMany(targetDocument=ChildDocumentWithoutDiscriminator::class) */
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
    /** @ODM\ReferenceOne(targetDocument=ChildDocumentWithDiscriminator::class) */
    protected $referencedChild;

    /** @ODM\ReferenceMany(targetDocument=ChildDocumentWithDiscriminator::class) */
    protected $referencedChildren;

    /** @ODM\EmbedOne(targetDocument=ChildDocumentWithDiscriminator::class) */
    protected $embeddedChild;

    /** @ODM\EmbedMany(targetDocument=ChildDocumentWithDiscriminator::class) */
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
    /** @ODM\Field(type="string") */
    protected $value;

    public function __construct($type, $value)
    {
        parent::__construct($type);
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
