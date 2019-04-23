<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH2015Test extends BaseTest
{
    public function testDocumentClassUnlistedInDiscriminatorMapFalsePositive()
    {
        $referencedDocument = $this->dm
            ->getRepository(GH2015ReferencedDocumentBase::class)
            ->findOneBy([]);

        if (!$referencedDocument) {
            $referencedDocument = new GH2015ReferencedDocumentConcrete();
        }

        $referencingDocument = $this->dm
            ->getRepository(GH2015ReferencingDocument::class)
            ->findOneBy([]);

        if (!$referencingDocument) {
            $referencingDocument = new GH2015ReferencingDocument();
        }

        $referencingDocument->referencedDocument->clear();
        $referencingDocument->referencedDocument[] = $referencedDocument;

        $this->dm->persist($referencedDocument);
        $this->dm->persist($referencingDocument);

        $this->dm->flush();
        $this->dm->clear();

        $referencingDocumentReloaded = $this->dm
            ->getRepository(GH2015ReferencingDocument::class)
            ->find($referencingDocument->id);

        //exit;
        //Uncomment this and run the test multiple times. You will see
        // in the database that the discriminator value is missing.

        $this->assertEquals(
            GH2015ReferencedDocumentConcrete::class,
            $this->dm->getClassNameResolver()->getRealClass(
                get_class($referencingDocumentReloaded->referencedDocument->last())
            )
        );

    }
}

/**
 * @ODM\Document(collection="GH2015Referencing")
 * @ODM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH2015ReferencingDocument {
    /**
     * @ODM\Id
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceMany(targetDocument="\Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2015ReferencedDocumentBase")
     * @var GH2015ReferencedDocumentBase
     */
    public $referencedDocument;

    public function __construct()
    {
        $this->referencedDocument = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @ODM\Document(collection="GH2015Referenced")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("class")
 */
class GH2015ReferencedDocumentBase {
    /**
     * @ODM\Id
     * @var string
     */
    public $id;
}

/**
 * @ODM\Document(collection="GH2015Referenced")
 */
class GH2015ReferencedDocumentConcrete extends GH2015ReferencedDocumentBase {

}

