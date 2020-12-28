<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\Driver\Exception\CommandException;

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

    public function testGeneratingIndexesWithTooLongIndexNameThrowsExceptionBeforeMongoDB42()
    {
        $this->skipOnMongoDB42('Index name restrictions were removed in MongoDB 4.2.');

        // Ensure that at least the beginning of the index name is contained in
        // the exception message. This can vary between driver/server versions.
        $this->expectException(CommandException::class);
        $this->expectExceptionMessageMatches('#GH1344LongIndexName.\$embedded1_this_is_a_really_long_name_that#');

        $this->dm->getSchemaManager()->ensureDocumentIndexes(GH1344LongIndexName::class);
    }

    public function testGeneratingIndexesWithLongIndexNameDoesNotThrowExceptionAfterMongoDB42()
    {
        $this->requireMongoDB42('Index name length is limited before MongoDB 4.2.');

        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes(GH1344LongIndexName::class);
        self::assertCount(1, $indexes);
        self::assertSame('embedded1_this_is_a_really_long_name_that_will_cause_problems_for_whoever_tries_to_use_it_whether_in_an_embedded_field_or_not', $indexes[0]['options']['name']);

        $this->dm->getSchemaManager()->ensureDocumentIndexes(GH1344LongIndexName::class);
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
class GH1344LongIndexName
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=GH1344LongIndexNameEmbedded::class) */
    public $embedded1;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\Index(keys={"property"="asc"}, name="this_is_a_really_long_name_that_will_cause_problems_for_whoever_tries_to_use_it_whether_in_an_embedded_field_or_not")
 */
class GH1344LongIndexNameEmbedded
{
    /** @ODM\Field */
    public $property;
}
