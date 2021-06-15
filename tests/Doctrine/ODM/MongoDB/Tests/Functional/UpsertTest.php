<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

use function assert;

class UpsertTest extends BaseTest
{
    /**
     * Tests for "MongoCursorException: Cannot apply $push/$pushAll modifier to non-array" error.
     *
     * Embedded document with provided id should not be upserted.
     */
    public function testUpsertEmbedManyDoesNotCreateObject()
    {
        $test = new UpsertTestUser();

        $embedded       = new UpsertTestUserEmbedded();
        $embedded->test = 'test';

        $test->embedMany[] = $embedded;

        $this->dm->persist($test);

        $this->assertFalse($this->uow->isScheduledForInsert($test));
        $this->assertTrue($this->uow->isScheduledForUpsert($test));

        $this->assertTrue($this->uow->isScheduledForInsert($embedded));
        $this->assertFalse($this->uow->isScheduledForUpsert($embedded));

        $this->dm->flush();
    }

    public function testUpsertDoesNotOverwriteNullableFieldsOnNull()
    {
        $test = new UpsertTestUser();

        $test->nullableField        = 'value';
        $test->nullableReferenceOne = new UpsertTestUser();
        $test->nullableEmbedOne     = new UpsertTestUserEmbedded();

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $upsert = new UpsertTestUser();

        // Re-use old ID but don't set any other values
        $upsert->id = $test->id;

        $this->dm->persist($upsert);
        $this->dm->flush();
        $this->dm->clear();

        $upsertResult = $this->dm->find(UpsertTestUser::class, $test->id);
        assert($upsertResult instanceof $upsertResult);
        self::assertNotNull($upsertResult->nullableField);
        self::assertNotNull($upsertResult->nullableReferenceOne);
        self::assertNotNull($upsertResult->nullableEmbedOne);
    }

    public function testUpsertsWritesNullableFieldsOnInsert()
    {
        $test = new UpsertTestUser();
        $this->dm->persist($test);
        $this->dm->flush();

        $collection = $this->dm->getDocumentCollection(UpsertTestUser::class);
        $result     = $collection->findOne(['_id' => new ObjectId($test->id)]);

        self::assertEquals(
            [
                '_id' => new ObjectId($test->id),
                'nullableField' => null,
                'nullableReferenceOne' => null,
                'nullableEmbedOne' => null,
            ],
            $result
        );
    }
}

/** @ODM\Document */
class UpsertTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(nullable=true) */
    public $nullableField;

    /** @ODM\EmbedOne(targetDocument=UpsertTestUserEmbedded::class, nullable=true) */
    public $nullableEmbedOne;

    /** @ODM\ReferenceOne(targetDocument=UpsertTestUser::class, cascade="persist", nullable=true) */
    public $nullableReferenceOne;

    /** @ODM\EmbedMany(targetDocument=UpsertTestUserEmbedded::class) */
    public $embedMany;

    public function __construct()
    {
        $this->id = (string) new ObjectId();
    }
}

/** @ODM\EmbeddedDocument */
class UpsertTestUserEmbedded
{
    /** @ODM\Field(type="string") */
    public $test;
}
