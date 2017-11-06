<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;

class GH1671Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function createAndUpdateField($documentClass, $textUpdate)
    {
        $document = new $documentClass;
        $document->text = "value";
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $updateDoc = new $documentClass;
        $updateDoc->id = $document->id;
        $updateDoc->text = $textUpdate;
        $this->dm->persist($updateDoc);
        $this->dm->flush();
        $this->dm->clear();

        return $this->dm->getRepository($documentClass)->find($document->id);
    }

    public function testUpsertNullDefaultField()
    {
        $updateDoc = $this->createAndUpdateField(GH1671Document::class, null);
        $this->assertNull($updateDoc->text);
    }

    public function testUpsertNullNullableField()
    {
        $updateDoc = $this->createAndUpdateField(GH1671NullableDocument::class, null);
        $this->assertNull($updateDoc->text);
    }

    public function testUpsertValueDefaultField()
    {
        $updateDoc = $this->createAndUpdateField(GH1671Document::class, '1');
        $this->assertEquals('1', $updateDoc->text);
    }


}

/** @ODM\Document */
class GH1671Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $text;
}

/** @ODM\Document */
class GH1671NullableDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string", nullable=true) */
    public $text;
}
