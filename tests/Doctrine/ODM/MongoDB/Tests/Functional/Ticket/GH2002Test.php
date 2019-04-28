<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH2002Test extends BaseTest
{
    /**
     * @dataProvider getReferenceData
     */
    public function testBuildingReferenceCreatesCorrectStructure(array $expectedReference, $document)
    {
        $this->dm->persist($document);

        $metadata = $this->dm->getClassMetadata(get_class($document));
        $this->dm->getUnitOfWork()->computeChangeSet($metadata, $document);

        $data = $this->dm->getUnitOfWork()->getPersistenceBuilder()->prepareInsertData($document);

        self::assertArraySubset($expectedReference, $data['parentDocument']);
    }

    public function getReferenceData()
    {
        return [
            'discriminatedDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', 'class' => GH2002DocumentA::class],
                'document' => new GH2002DocumentB(new GH2002DocumentA()),
            ],
            'referenceWithoutTargetDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', '_doctrine_class_name' => GH2002DocumentA::class],
                'document' => new GH2002ReferenceWithoutTargetDocument(new GH2002DocumentA()),
            ],
            'referenceWithoutTargetDocumentWithDiscriminatorField' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', 'referencedClass' => GH2002DocumentA::class],
                'document' => new GH2002ReferenceWithoutTargetDocumentWithDiscriminatorField(new GH2002DocumentA()),
            ],
            'referenceWithDiscriminatorField' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', 'referencedClass' => GH2002DocumentA::class],
                'document' => new GH2002ReferenceWithDiscriminatorField(new GH2002DocumentA()),
            ],
            'referenceWithPartialDiscriminatorMapUnlistedDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', 'referencedClass' => GH2002DocumentA::class],
                'document' => new GH2002ReferenceWithPartialDiscriminatorMap(new GH2002DocumentA()),
            ],
            'referenceWithPartialDiscriminatorMapListedDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentA', 'referencedClass' => 'B'],
                'document' => new GH2002ReferenceWithPartialDiscriminatorMap(new GH2002DocumentB()),
            ],
            'documentWithDiscriminatorMapListedDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentWithDiscriminatorMapA', 'type' => 'A'],
                'document' => new GH2002DocumentWithDiscriminatorMapA(new GH2002DocumentWithDiscriminatorMapA()),
            ],
            'documentWithDiscriminatorMapUnlistedDocument' => [
                'expectedReference' => ['$ref' => 'GH2002DocumentWithDiscriminatorMapA', 'type' => GH2002DocumentWithDiscriminatorMapB::class],
                'document' => new GH2002DocumentWithDiscriminatorMapA(new GH2002DocumentWithDiscriminatorMapB()),
            ],
        ];
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("class")
 */
class GH2002DocumentA
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH2002DocumentA::class, cascade="all")
     *
     * @var GH2002DocumentA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 */
class GH2002DocumentB extends GH2002DocumentA
{
}

/**
 * @ODM\Document
 */
class GH2002ReferenceWithoutTargetDocument
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(cascade="all")
     *
     * @var GH2002DocumentA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 */
class GH2002ReferenceWithoutTargetDocumentWithDiscriminatorField
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(discriminatorField="referencedClass", cascade="all")
     *
     * @var GH2002DocumentA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 */
class GH2002ReferenceWithDiscriminatorField
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH2002DocumentA::class, discriminatorField="referencedClass", cascade="all")
     *
     * @var GH2002DocumentA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 */
class GH2002ReferenceWithPartialDiscriminatorMap
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(discriminatorField="referencedClass", discriminatorMap={"B"=GH2002DocumentB::class}, cascade="all")
     *
     * @var GH2002DocumentA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"A"=GH2002DocumentWithDiscriminatorMapA::class})
 */
class GH2002DocumentWithDiscriminatorMapA
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH2002DocumentWithDiscriminatorMapA::class, cascade="all")
     *
     * @var GH2002DocumentWithDiscriminatorMapA
     */
    public $parentDocument;

    public function __construct(GH2002DocumentWithDiscriminatorMapA $parentDocument = null)
    {
        $this->parentDocument = $parentDocument;
    }
}

/**
 * @ODM\Document
 */
class GH2002DocumentWithDiscriminatorMapB extends GH2002DocumentWithDiscriminatorMapA
{
}
