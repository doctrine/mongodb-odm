<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH665Test extends BaseTest
{
    public function testUseAddToSetStrategyOnEmbeddedDocument()
    {
        $document = new GH665Document();
        $document->embeddedPushAll->add(new GH665Embedded('foo'));
        $document->embeddedAddToSet->add(new GH665Embedded('bar'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(GH665Document::class)
            ->findOne(['embeddedPushAll.name' => 'foo']);
        $this->assertNotNull($check);
        $this->assertSame($document->id, (string) $check['_id']);

        $check = $this->dm->getDocumentCollection(GH665Document::class)
            ->findOne(['embeddedAddToSet.name' => 'bar']);
        $this->assertNotNull($check);
        $this->assertSame($document->id, (string) $check['_id']);

        $persisted = $this->dm->createQueryBuilder(GH665Document::class)
            ->hydrate(false)
            ->field('id')->equals($document->id)
            ->getQuery()
            ->getSingleResult();

        $expected = [
            '_id' => $document->id,
            'embeddedPushAll' => [['name' => 'foo']],
            'embeddedAddToSet' => [['name' => 'bar']],
        ];

        $this->assertEquals($expected, $persisted);
    }
}

/** @ODM\Document */
class GH665Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument=GH665Embedded::class, strategy="pushAll") */
    public $embeddedPushAll;

    /** @ODM\EmbedMany(targetDocument=GH665Embedded::class, strategy="addToSet") */
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
