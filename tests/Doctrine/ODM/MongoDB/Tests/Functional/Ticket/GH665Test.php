<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

class GH665Test extends BaseTest
{

    public function testUseAddToSetStrategyOnEmbeddedDocument()
    {
        $embedded = new GH665Embedded();
        $embedded->name = 'test';

        $document = new GH665Document();
        $document->embedded->add($embedded);
        $document->embeddedset->add($embedded);
        $this->dm->persist($document);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\GH665Document')
            ->findOne(array('embedded.name' => 'test'));
        $this->assertNotNull($check);

        $persisted = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH665Document')
            ->hydrate(false)
            ->field('id')->equals($document->id)
            ->getQuery()
            ->getSingleResult();

        $expected = array(
            '_id' => $document->id,
            'embedded' => array(
                array('name' => 'test')
                ),
            'embeddedset' => array(
                array('name' => 'test')
                )
            );
        $this->assertEquals($expected, $persisted);

        // the dot.notation should work.
        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\GH665Document')
            ->findOne(array('embeddedset.name' => 'test'));
        $this->assertNotNull($check);

    }
}

/** @ODM\Document */
class GH665Document
{
    /** @ODM\Id */
    public $id;

    /**
      * @ODM\EmbedMany(targetDocument="GH665Embedded")
      */
    public $embedded;

    /**
      * @ODM\EmbedMany(targetDocument="GH665Embedded",strategy="addToSet")
      */
    public $embeddedset;

    public function __construct() {
        $this->embedded = new ArrayCollection();
        $this->embeddedset = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH665Embedded
{
    /** @ODM\String */
    public $name;
}



?>
