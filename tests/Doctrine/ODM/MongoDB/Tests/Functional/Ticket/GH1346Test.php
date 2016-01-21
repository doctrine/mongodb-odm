<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1346Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @group GH1346Test
     */
    public function testPublicDistanceProperty()
    {
        $coordinates = new GH1346Coordinates();
        $coordinates->setLatitude(10);
        $coordinates->setLongitude(10);

        $refrenced1 = new GH1346ReferencedDocument();
        $refrenced1->setCoordinates($coordinates);

        $refrenced2 = new GH1346ReferencedDocument();
        $refrenced2->setCoordinates($coordinates);

        $gH1346Document = new GH1346Document();

        $this->dm->persist($refrenced2);
        $this->dm->persist($refrenced1);
        $this->dm->persist($gH1346Document);
        $this->dm->flush();

        $gH1346Document->addReference($refrenced1);

        $this->dm->persist($gH1346Document);
        $this->dm->flush();
        $this->dm->clear();

        $gH1346Document = $this->dm->getRepository(__NAMESPACE__ . '\GH1346Document')->find($gH1346Document->getId());
        $refrenced2 = $this->dm->getRepository(__NAMESPACE__ . '\GH1346ReferencedDocument')->find($refrenced2->getId());

        $gH1346Document->hasReference($refrenced2);
        $gH1346Document->addReference($refrenced2);

        $this->dm->persist($gH1346Document);
        $this->dm->flush();

        $this->assertEquals(2, $gH1346Document->getReferences()->count());

        $this->dm->remove($gH1346Document);
        $this->dm->remove($refrenced2);
        $this->dm->remove($refrenced2);
        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * @group GH1346Test
     */
    public function testPublicProperty()
    {

        $refrenced1 = new GH1346OtherReferencedDocument();

        $refrenced2 = new GH1346OtherReferencedDocument();

        $gH1346Document = new GH1346Document();

        $this->dm->persist($refrenced2);
        $this->dm->persist($refrenced1);
        $this->dm->persist($gH1346Document);
        $this->dm->flush();

        $gH1346Document->addOtherReference($refrenced1);

        $this->dm->persist($gH1346Document);
        $this->dm->flush();
        $this->dm->clear();

        $gH1346Document = $this->dm->getRepository(__NAMESPACE__ . '\GH1346Document')->find($gH1346Document->getId());
        $refrenced2 = $this->dm->getRepository(__NAMESPACE__ . '\GH1346OtherReferencedDocument')->find($refrenced2->getId());

//        $gH1346Document->hasReference($refrenced2);
        $gH1346Document->addOtherReference($refrenced2);

        $this->dm->persist($gH1346Document);
        $this->dm->flush();

        $this->assertEquals(2, $gH1346Document->getOtherReferences()->count());

        $this->dm->remove($gH1346Document);
        $this->dm->remove($refrenced2);
        $this->dm->remove($refrenced2);
        $this->dm->flush();
        $this->dm->clear();
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

    /** @ODM\ReferenceMany(targetDocument="GH1346OtherReferencedDocument") */
    protected $otherReferences;

    public function __construct()
    {
        $this->references = new ArrayCollection();
        $this->otherReferences = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getReferences()
    {
        return $this->references;
    }

    public function setReferences($references)
    {
        $this->references = $references;
    }

    public function hasReference($reference)
    {
        return $this->references->contains($reference);
    }

    public function addReference($reference)
    {
        $this->references->add($reference);
    }

    public function addOtherReference($otherReference)
    {
        $this->otherReferences->add($otherReference);
    }

    public function getOtherReferences()
    {
        return $this->otherReferences;
    }

    public function setOtherReferences($otherReferences)
    {
        $this->otherReferences = $otherReferences;
    }

}


/**
 * @ODM\Document
 * @ODM\Index(keys={"coordinates"="2d"})
 */
class GH1346ReferencedDocument
{
    /** @ODM\Distance */
    public $distance;

    /** @ODM\String() */
    public $test2;

    /** @ODM\Id */
    protected $id;
    /** @ODM\EmbedOne(targetDocument="GH1346Coordinates") */
    protected $coordinates;

    public function getCoordinates()
    {
        return $this->coordinates;
    }

    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
    }

    public function getDistance()
    {
        return $this->distance;
    }

    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}


/**
 * @ODM\Document
 */
class GH1346OtherReferencedDocument
{
    /** @ODM\String() */
    public $test2;

    /** @ODM\Id */
    protected $id;

    /**
     * @return mixed
     */
    public function getTest2()
    {
        return $this->test2;
    }

    /**
     * @param mixed $test2
     */
    public function setTest2($test2)
    {
        $this->test2 = $test2;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}

/** @ODM\EmbeddedDocument */
class GH1346Coordinates
{
    /** @ODM\Float */
    public $latitude;

    /** @ODM\Float */
    public $longitude;

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }
}
