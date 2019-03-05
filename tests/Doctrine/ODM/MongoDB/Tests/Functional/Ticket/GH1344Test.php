<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1344Test extends BaseTest
{
    public function testGeneratingIndexesDoesNotThrowException()
    {
        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes(GH1344Main::class);
        self::assertCount(4, $indexes);
        self::assertSame('embedded1_embedded', $indexes[0]['options']['name']);
        self::assertSame('embedded1_embedded_nested', $indexes[1]['options']['name']);
        self::assertSame('embedded2_embedded', $indexes[2]['options']['name']);
        self::assertSame('embedded2_embedded_nested', $indexes[3]['options']['name']);

        $this->dm->getSchemaManager()->ensureDocumentIndexes(GH1344Main::class);
    }
}

/** @ODM\Document */
class GH1344Main
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=GH1344Embedded::class) */
    public $embedded1;

    /** @ODM\EmbedOne(targetDocument=GH1344Embedded::class) */
    public $embedded2;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\Index(keys={"property"="asc"}, name="embedded")
 */
class GH1344Embedded
{
    /** @ODM\Field */
    public $property;

    /** @ODM\EmbedOne(targetDocument=GH1344EmbeddedNested::class) */
    public $embedded;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\Index(keys={"property"="asc"}, name="nested")
 */
class GH1344EmbeddedNested
{
    /** @ODM\Field */
    public $property;
}
