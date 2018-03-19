<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1671Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public static function upsertData()
    {
        return [
            'nullValueInNonNullableField' => [GH1671Document::class, null],
            'nullValueInNullableField' => [GH1671NullableDocument::class, null],
            'nonNullValueInNonNullableField' => [GH1671Document::class, '1'],
        ];
    }

    /**
     * @dataProvider upsertData
     */
    public function testUpsert($documentClass, $value)
    {
        $document = new $documentClass;
        $document->text = "value";
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $updateDoc = new $documentClass;
        $updateDoc->id = $document->id;
        $updateDoc->text = $value;
        $this->dm->persist($updateDoc);
        $this->dm->flush();
        $this->dm->clear();

        $dbDocument = $this->dm->getRepository($documentClass)->find($document->id);

        self::assertSame($value, $dbDocument->text);
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
