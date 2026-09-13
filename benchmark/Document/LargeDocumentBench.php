<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\FlatDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\NestedDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\NestedIntItem;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\NestedStrItem;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use MongoDB\BSON\ObjectId;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function assert;
use function file_get_contents;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Exercises hydration, storage and loading of documents shaped like the
 * "small_doc" and "large_doc_nested" fixtures from the MongoDB driver
 * benchmark corpus: a wide flat document and a document with many
 * embedded sub-documents. Complements HydrateDocumentBench,
 * StoreDocumentBench and LoadDocumentBench, which focus on individual
 * association types rather than overall document scale.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(50)]
#[Iterations(5)]
final class LargeDocumentBench extends BaseBench
{
    /** @var array<string, mixed> */
    private static array $flatData;

    /** @var array<string, mixed> */
    private static array $nestedData;

    private static HydratorInterface $flatHydrator;

    private static HydratorInterface $nestedHydrator;

    private static string $flatDocumentId;

    private static string $nestedDocumentId;

    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create(__DIR__ . '/../Fixtures');
    }

    public function init(): void
    {
        self::$flatData = (array) json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/data/flat_document.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $nestedJson = (array) json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/data/nested_document.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::$nestedData = [
            'embeddedStrDoc1' => $nestedJson['embedded_str_doc_1'],
            'embeddedStrDoc2' => $nestedJson['embedded_str_doc_2'],
            'embeddedStrDoc3' => $nestedJson['embedded_str_doc_3'],
            'embeddedStrDoc4' => $nestedJson['embedded_str_doc_4'],
            'embeddedStrDoc5' => $nestedJson['embedded_str_doc_5'],
            'embeddedStrDocArray' => $nestedJson['embedded_str_doc_array'],
            'embeddedIntDoc8' => $nestedJson['embedded_int_doc_8'],
            'embeddedIntDoc9' => $nestedJson['embedded_int_doc_9'],
            'embeddedIntDoc10' => $nestedJson['embedded_int_doc_10'],
            'embeddedIntDoc11' => $nestedJson['embedded_int_doc_11'],
            'embeddedIntDoc12' => $nestedJson['embedded_int_doc_12'],
            'embeddedIntDoc13' => $nestedJson['embedded_int_doc_13'],
            'embeddedIntDoc14' => $nestedJson['embedded_int_doc_14'],
        ];

        self::$flatHydrator   = $this->getDocumentManager()->getHydratorFactory()->getHydratorFor(FlatDocument::class);
        self::$nestedHydrator = $this->getDocumentManager()->getHydratorFactory()->getHydratorFor(NestedDocument::class);

        $flatDocument = $this->newFlatDocument();
        $this->getDocumentManager()->persist($flatDocument);

        $nestedDocument = $this->newNestedDocument();
        $this->getDocumentManager()->persist($nestedDocument);

        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();

        self::$flatDocumentId   = $flatDocument->id;
        self::$nestedDocumentId = $nestedDocument->id;
    }

    public function benchHydrateFlatDocument(): void
    {
        self::$flatHydrator->hydrate(new FlatDocument(), self::$flatData + ['_id' => new ObjectId()]);
    }

    public function benchHydrateNestedDocument(): void
    {
        self::$nestedHydrator->hydrate(new NestedDocument(), self::$nestedData + ['_id' => new ObjectId()]);
    }

    public function benchStoreFlatDocument(): void
    {
        $this->getDocumentManager()->persist($this->newFlatDocument());
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreNestedDocument(): void
    {
        $this->getDocumentManager()->persist($this->newNestedDocument());
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchLoadFlatDocument(): void
    {
        $this->getDocumentManager()->find(FlatDocument::class, self::$flatDocumentId);
    }

    public function benchLoadNestedDocument(): void
    {
        $document = $this->getDocumentManager()->find(NestedDocument::class, self::$nestedDocumentId);
        assert($document instanceof NestedDocument);

        foreach ($document->embeddedStrDocArray as $item) {
            assert($item instanceof NestedStrItem);
        }
    }

    private function newFlatDocument(): FlatDocument
    {
        $document          = new FlatDocument();
        $document->field1  = self::$flatData['field1'];
        $document->field2  = self::$flatData['field2'];
        $document->field3  = self::$flatData['field3'];
        $document->field4  = self::$flatData['field4'];
        $document->field5  = self::$flatData['field5'];
        $document->field6  = self::$flatData['field6'];
        $document->field7  = self::$flatData['field7'];
        $document->field8  = self::$flatData['field8'];
        $document->field9  = self::$flatData['field9'];
        $document->field10 = self::$flatData['field10'];
        $document->field11 = self::$flatData['field11'];
        $document->field12 = self::$flatData['field12'];
        $document->field13 = self::$flatData['field13'];

        return $document;
    }

    private function newNestedDocument(): NestedDocument
    {
        $document = new NestedDocument();

        $document->embeddedStrDoc1 = $this->newStrItem(self::$nestedData['embeddedStrDoc1']);
        $document->embeddedStrDoc2 = $this->newStrItem(self::$nestedData['embeddedStrDoc2']);
        $document->embeddedStrDoc3 = $this->newStrItem(self::$nestedData['embeddedStrDoc3']);
        $document->embeddedStrDoc4 = $this->newStrItem(self::$nestedData['embeddedStrDoc4']);
        $document->embeddedStrDoc5 = $this->newStrItem(self::$nestedData['embeddedStrDoc5']);

        foreach (self::$nestedData['embeddedStrDocArray'] as $item) {
            $document->embeddedStrDocArray[] = $this->newStrItem($item);
        }

        $document->embeddedIntDoc8  = $this->newIntItem(self::$nestedData['embeddedIntDoc8']);
        $document->embeddedIntDoc9  = $this->newIntItem(self::$nestedData['embeddedIntDoc9']);
        $document->embeddedIntDoc10 = $this->newIntItem(self::$nestedData['embeddedIntDoc10']);
        $document->embeddedIntDoc11 = $this->newIntItem(self::$nestedData['embeddedIntDoc11']);
        $document->embeddedIntDoc12 = $this->newIntItem(self::$nestedData['embeddedIntDoc12']);
        $document->embeddedIntDoc13 = $this->newIntItem(self::$nestedData['embeddedIntDoc13']);
        $document->embeddedIntDoc14 = $this->newIntItem(self::$nestedData['embeddedIntDoc14']);

        return $document;
    }

    /** @param array<string, string> $data */
    private function newStrItem(array $data): NestedStrItem
    {
        $item = new NestedStrItem();
        foreach ($data as $field => $value) {
            $item->$field = $value;
        }

        return $item;
    }

    /** @param array<string, int> $data */
    private function newIntItem(array $data): NestedIntItem
    {
        $item = new NestedIntItem();
        foreach ($data as $field => $value) {
            $item->$field = $value;
        }

        return $item;
    }
}
