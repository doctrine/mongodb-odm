<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
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
    public function testUpsertEmbedManyDoesNotCreateObject(): void
    {
        $test = new UpsertTestUser();

        $embedded       = new UpsertTestUserEmbedded();
        $embedded->test = 'test';

        $test->embedMany[] = $embedded;

        $this->dm->persist($test);

        self::assertFalse($this->uow->isScheduledForInsert($test));
        self::assertTrue($this->uow->isScheduledForUpsert($test));

        self::assertTrue($this->uow->isScheduledForInsert($embedded));
        self::assertFalse($this->uow->isScheduledForUpsert($embedded));

        $this->dm->flush();
    }

    public function testUpsertDoesNotOverwriteNullableFieldsOnNull(): void
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
        assert($upsertResult instanceof UpsertTestUser);
        self::assertNotNull($upsertResult->nullableField);
        self::assertNotNull($upsertResult->nullableReferenceOne);
        self::assertNotNull($upsertResult->nullableEmbedOne);
    }

    public function testUpsertsWritesNullableFieldsOnInsert(): void
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
            $result,
        );
    }
}

/** @ODM\Document */
class UpsertTestUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(nullable=true)
     *
     * @var string|null
     */
    public $nullableField;

    /**
     * @ODM\EmbedOne(targetDocument=UpsertTestUserEmbedded::class, nullable=true)
     *
     * @var UpsertTestUserEmbedded|null
     */
    public $nullableEmbedOne;

    /**
     * @ODM\ReferenceOne(targetDocument=UpsertTestUser::class, cascade="persist", nullable=true)
     *
     * @var UpsertTestUser|null
     */
    public $nullableReferenceOne;

    /**
     * @ODM\EmbedMany(targetDocument=UpsertTestUserEmbedded::class)
     *
     * @var Collection<int, UpsertTestUserEmbedded>
     */
    public $embedMany;

    public function __construct()
    {
        $this->id = (string) new ObjectId();
    }
}

/** @ODM\EmbeddedDocument */
class UpsertTestUserEmbedded
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $test;
}
