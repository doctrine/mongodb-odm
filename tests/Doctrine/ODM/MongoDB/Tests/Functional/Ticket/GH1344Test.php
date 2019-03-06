<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoResultException;

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

    public function testGeneratingIndexesWithTooLongIndexNameThrowsException()
    {
        // Ensure that at least the beginning of the index name is contained in
        // the exception message. This can vary between driver/server versions.
        $this->expectException(MongoResultException::class);
        $this->expectExceptionMessageRegExp('#GH1344TooLongIndexName.\$embedded1_this_is_a_really_long_name_that#');

        $this->dm->getSchemaManager()->ensureDocumentIndexes(GH1344TooLongIndexName::class);
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

/** @ODM\Document */
class GH1344TooLongIndexName
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=GH1344TooLongIndexNameEmbedded::class) */
    public $embedded1;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\Index(keys={"property"="asc"}, name="this_is_a_really_long_name_that_will_cause_problems_for_whoever_tries_to_use_it_whether_in_an_embedded_field_or_not")
 */
class GH1344TooLongIndexNameEmbedded
{
    /** @ODM\Field */
    public $property;
}
