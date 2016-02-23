<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH665Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testUseAddToSetStrategyOnEmbeddedDocument()
    {
        $document = new GH665Document();
        $document->embeddedPushAll->add(new GH665Embedded('foo'));
        $document->embeddedAddToSet->add(new GH665Embedded('bar'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\GH665Document')
            ->findOne(array('embeddedPushAll.name' => 'foo'));
        $this->assertNotNull($check);
        $this->assertSame($document->id, (string) $check['_id']);

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\GH665Document')
            ->findOne(array('embeddedAddToSet.name' => 'bar'));
        $this->assertNotNull($check);
        $this->assertSame($document->id, (string) $check['_id']);

        $persisted = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH665Document')
            ->hydrate(false)
            ->field('id')->equals($document->id)
            ->getQuery()
            ->getSingleResult();

        $expected = array(
            '_id' => $document->id,
            'embeddedPushAll' => array(array('name' => 'foo')),
            'embeddedAddToSet' => array(array('name' => 'bar'))
        );

        $this->assertEquals($expected, $persisted);
    }
}

/** @ODM\Document */
class GH665Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH665Embedded", strategy="pushAll") */
    public $embeddedPushAll;

    /** @ODM\EmbedMany(targetDocument="GH665Embedded", strategy="addToSet") */
    public $embeddedAddToSet;

    public function __construct()
    {
        $this->embeddedPushAll = new ArrayCollection();
        $this->embeddedAddToSet = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH665Embedded
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
