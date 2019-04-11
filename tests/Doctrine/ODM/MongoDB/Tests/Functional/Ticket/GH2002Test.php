<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH2002Test extends BaseTest
{
    private $documentAId;

    public function setUp()
    {
        parent::setUp();
        $documentA = new GH2002DocumentA();

        $this->dm->persist($documentA);
        $this->documentAId = $documentA->id;

        $this->dm->flush();
        $this->dm->clear();
        // Reset the metadata for the GH2002DocumentA to simulate new request
        $this->dm->getMetadataFactory()->setMetadataFor(GH2002DocumentA::class, null);
    }

    public function testDocumentClassUnlistedInDiscriminatorMapFalsePositive()
    {
        $documentA = $this->dm
            ->getRepository(GH2002DocumentA::class)
            ->find($this->documentAId);

        $documentB = new GH2002DocumentB();
        $documentB->parentDocument = $documentA;

        $this->dm->persist($documentB);
        $this->dm->flush();

        // So that phpunit does not complain about not performing any
        // assertions when fixed
        $this->assertEquals(1, 1);
    }
}

/**
 * @ODM\Document(collection="GH2002DocumentA")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("class")
 * @ODM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH2002DocumentA {
    /**
     * @ODM\Id
     * @var string
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument="GH2002DocumentA")
     * @var GH2002DocumentA
     */
    public $parentDocument;
}

/**
 * @ODM\Document(collection="GH2002DocumentA")
 */
class GH2002DocumentB extends GH2002DocumentA {
}

