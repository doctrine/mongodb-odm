<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class ReferenceDiscriminatorsDefaultValueTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

        $children = array($firstChildWithoutDiscriminator, $secondChildWithoutDiscriminator);
        $this->dm->persist($parentWithoutDiscriminator = new ParentDocumentWithoutDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $childWithDiscriminator = $this->dm->find(__NAMESPACE__ . '\ChildDocumentWithDiscriminator', $firstChildWithoutDiscriminator->getId());
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $childWithDiscriminator);
        $this->assertSame('firstWithoutDiscriminator', $childWithDiscriminator->getType(), 'New mapping correctly loads legacy document');

        $parentWithDiscriminator = $this->dm->find(__NAMESPACE__ . '\ParentDocumentWithDiscriminator', $parentWithoutDiscriminator->getId());
        $this->assertNotNull($parentWithDiscriminator);
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $parentWithDiscriminator->getReferencedChild(), 'Referenced document correctly respects defaultDiscriminatorValue in referenceOne mapping');
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document correctly respects defaultDiscriminatorValue in referenceOne mapping');

        foreach ($parentWithDiscriminator->getReferencedChildren() as $child) {
            $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $child, 'Referenced document correctly respects defaultDiscriminatorValue in referenceMany mapping');
        }

        foreach ($parentWithDiscriminator->getEmbeddedChildren() as $child) {
            $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $child, 'Embedded document correctly respects defaultDiscriminatorValue in referenceMany mapping');
        }
    }

    /**
     * This test ensures that a discriminatorValue is stored in the database and overrides the default
     */
    public function testLoadDocumentWithDifferentChild()
    {
        $this->dm->persist($firstChildWithDiscriminator = new ChildDocumentWithDiscriminatorComplex('firstWithDiscriminator', 'veryComplex'));
        $this->dm->persist($secondChildWithDiscriminator = new ChildDocumentWithDiscriminatorSimple('secondWithDiscriminator'));

        $children = array($firstChildWithDiscriminator, $secondChildWithDiscriminator);
        $this->dm->persist($parentWithDiscriminator = new ParentDocumentWithDiscriminator($children));

        $this->dm->flush();
        $this->dm->clear();

        $parentWithDiscriminator = $this->dm->find(__NAMESPACE__ . '\ParentDocumentWithDiscriminator', $parentWithDiscriminator->getId());
        $this->assertNotNull($parentWithDiscriminator);
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorComplex', $parentWithDiscriminator->getReferencedChild(), 'Referenced document respects discriminatorValue if it is present in referenceOne mapping');
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorComplex', $parentWithDiscriminator->getEmbeddedChild(), 'Embedded document respects discriminatorValue if it is present in referenceOne mapping');

        // Check referenceMany mapping
        $referencedChildren = $parentWithDiscriminator->getReferencedChildren()->toArray();
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorComplex', $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('secondWithDiscriminator', $referencedChildren[1]->getType());

        // Check embedMany mapping
        $referencedChildren = $parentWithDiscriminator->getEmbeddedChildren()->toArray();
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorComplex', $referencedChildren[0], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
        $this->assertSame('firstWithDiscriminator', $referencedChildren[0]->getType());

        $this->assertInstanceOf(__NAMESPACE__ . '\ChildDocumentWithDiscriminatorSimple', $referencedChildren[1], 'Referenced document respects discriminatorValue if it is present in referenceMany mapping');
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
    /** @ODM\ReferenceOne(targetDocument="ChildDocumentWithoutDiscriminator") */
    protected $referencedChild;

    /** @ODM\ReferenceMany(targetDocument="ChildDocumentWithoutDiscriminator") */
    protected $referencedChildren;

    /** @ODM\EmbedOne(targetDocument="ChildDocumentWithoutDiscriminator") */
    protected $embeddedChild;

    /** @ODM\EmbedMany(targetDocument="ChildDocumentWithoutDiscriminator") */
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
    /** @ODM\ReferenceOne(targetDocument="ChildDocumentWithDiscriminator") */
    protected $referencedChild;

    /** @ODM\ReferenceMany(targetDocument="ChildDocumentWithDiscriminator") */
    protected $referencedChildren;

    /** @ODM\EmbedOne(targetDocument="ChildDocumentWithDiscriminator") */
    protected $embeddedChild;

    /** @ODM\EmbedMany(targetDocument="ChildDocumentWithDiscriminator") */
    protected $embeddedChildren;
}

/**
 * @ODM\Document(collection="discriminator_child")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="discriminator")
 * @ODM\DiscriminatorMap({"simple"="ChildDocumentWithDiscriminatorSimple", "complex"="ChildDocumentWithDiscriminatorComplex"})
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
