<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH897Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEventsFromOneClassComputeChangesetsForAnotherClass()
    {
        $documentA = new GH897A();
        $documentA->setName('Document A');

        $this->dm->persist($documentA);
        $this->dm->flush();
        $this->dm->clear();

        $documentA = $this->dm->find(__NAMESPACE__.'\GH897A', $documentA->getId());

        $documentB = new GH897B($this->dm);
        $documentB->setDocument($documentA);

        // Currently throws an E_NOTICE undefined index in UoW.
        $this->dm->persist($documentB);

        $this->assertSame('Document A Changed', $documentA->getName(), 'Change to Document A was not saved.');
    }
}

/** @ODM\Document */
class GH897A
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    // Return the identifier without triggering Proxy initialization
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}

/** @ODM\Document */
class GH897B
{
    public function __construct($dm)
    {
        $this->dm = $dm;
    }
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="GH897A") **/
    protected $document;

    // Return the identifier without triggering Proxy initialization
    public function getId()
    {
        return $this->id;
    }

    public function setDocument(GH897A $document)
    {
        $this->document = $document;
        return $this;
    }

    /** @ODM\PrePersist **/
    public function onPrePersist()
    {

        $documentA = $this->document;
        $documentA->setName('Document A Changed');

        $this->dm->getUnitOfWork()->recomputeSingleDocumentChangeSet(
            $this->dm->getClassMetadata(get_class($documentA)),
            $documentA
        );

    }
}
