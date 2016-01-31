<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1346Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @group GH1346Test
     */
    public function testPublicProperty()
    {
        $referenced1 = new GH1346ReferencedDocument();
        $referenced2 = new GH1346ReferencedDocument();
        $gH1346Document = new GH1346Document();
        $gH1346Document->addReference($referenced1);

        $this->dm->persist($referenced2);
        $this->dm->persist($referenced1);
        $this->dm->persist($gH1346Document);
        $this->dm->flush();
        $this->dm->clear();

        $gH1346Document = $this->dm->getRepository(__NAMESPACE__ . '\GH1346Document')->find($gH1346Document->getId());
        $referenced2 = $this->dm->getRepository(__NAMESPACE__ . '\GH1346ReferencedDocument')->find($referenced2->getId());

        $gH1346Document->addReference($referenced2);

        $this->dm->persist($gH1346Document);
        $this->dm->flush();

        $this->assertEquals(2, $gH1346Document->getReferences()->count());

        $this->dm->flush();
    }
}


/**
 * @ODM\Document
 */
class GH1346Document
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="GH1346ReferencedDocument") */
    protected $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addReference($otherReference)
    {
        $this->references->add($otherReference);
    }

    public function getReferences()
    {
        return $this->references;
    }
}

/**
 * @ODM\Document
 */
class GH1346ReferencedDocument
{
    /** @ODM\Field(type="string") */
    public $test;

    /** @ODM\Id */
    protected $id;

    public function setTest($test)
    {
        $this->test = $test;
    }

    public function getId()
    {
        return $this->id;
    }
}
