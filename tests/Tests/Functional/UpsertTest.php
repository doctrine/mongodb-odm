<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentIdentityCollisionException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;
use MongoDB\BSON\ObjectId;

use function array_filter;
use function array_values;
use function assert;
use function str_contains;

class UpsertTest extends BaseTestCase
{
    use CaptureDeprecationMessages;

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

    public function testPersistingDifferentDocumentWithSameIdTriggersDeprecation(): void
    {
        $first = new UpsertTestUser();
        $this->dm->persist($first);
        $this->dm->flush();

        $second     = new UpsertTestUser();
        $second->id = $first->id;

        $this->captureDeprecationMessages(
            fn () => $this->dm->persist($second),
            $errors,
        );

        self::assertCount(1, $this->filterIdentityMapDeprecations($errors));
    }

    public function testPersistingDifferentDocumentWithSameIdThrowsWhenEnabled(): void
    {
        $this->config->setRejectIdCollisionInIdentityMap(true);

        $first = new UpsertTestUser();
        $this->dm->persist($first);
        $this->dm->flush();

        $second     = new UpsertTestUser();
        $second->id = $first->id;

        $this->expectException(DocumentIdentityCollisionException::class);

        $this->dm->persist($second);
    }

    public function testUpsertingNewInstanceAfterClearDoesNotWarn(): void
    {
        $first = new UpsertTestUser();
        $this->dm->persist($first);
        $this->dm->flush();
        $this->dm->clear();

        $second     = new UpsertTestUser();
        $second->id = $first->id;

        $this->captureDeprecationMessages(
            function () use ($second): void {
                $this->dm->persist($second);
                $this->dm->flush();
            },
            $errors,
        );

        self::assertSame([], $this->filterIdentityMapDeprecations($errors));
    }

    /**
     * @param list<string> $errors
     *
     * @return list<string>
     */
    private function filterIdentityMapDeprecations(array $errors): array
    {
        return array_values(array_filter(
            $errors,
            static fn (string $error): bool => str_contains($error, 'already mapped to the same identifier'),
        ));
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

#[ODM\Document]
class UpsertTestUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(nullable: true)]
    public $nullableField;

    /** @var UpsertTestUserEmbedded|null */
    #[ODM\EmbedOne(targetDocument: UpsertTestUserEmbedded::class, nullable: true)]
    public $nullableEmbedOne;

    /** @var UpsertTestUser|null */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: 'persist', nullable: true)]
    public $nullableReferenceOne;

    /** @var Collection<int, UpsertTestUserEmbedded> */
    #[ODM\EmbedMany(targetDocument: UpsertTestUserEmbedded::class)]
    public $embedMany;

    public function __construct()
    {
        $this->id = (string) new ObjectId();
    }
}

#[ODM\EmbeddedDocument]
class UpsertTestUserEmbedded
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $test;
}
