<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\TimeSeries\TimeSeriesDocument;
use MongoDB\BSON\ObjectId;

use function iterator_to_array;

class TimeSeriesTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->requireMongoDB63('Time series tests require MonogDB 6.3 or newer');
    }

    public function testCreateTimeSeriesCollection(): void
    {
        $this->createTimeSeriesCollection(TimeSeriesDocument::class);

        $indexes = iterator_to_array($this->dm->getDocumentCollection(TimeSeriesDocument::class)->listIndexes());

        $this->assertCount(1, $indexes);

        self::assertSame(['metadata' => 1, 'time' => 1], $indexes[0]['key']);
    }

    public function testCreateTimeSeriesDocumentWithoutId(): void
    {
        $this->createTimeSeriesCollection(TimeSeriesDocument::class);

        $document           = new TimeSeriesDocument();
        $document->time     = new DateTime('2025-02-05T08:53:12+00:00');
        $document->value    = 9;
        $document->metadata = 'energy';

        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertCount(1, $this->dm->getDocumentCollection(TimeSeriesDocument::class)->find());
    }

    public function testCreateTimeSeriesDocumentWithId(): void
    {
        $this->createTimeSeriesCollection(TimeSeriesDocument::class);

        $document           = new TimeSeriesDocument();
        $document->id       = (string) new ObjectId();
        $document->time     = new DateTime('2025-02-05T08:53:12+00:00');
        $document->value    = 9;
        $document->metadata = 'energy';

        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertCount(1, $this->dm->getDocumentCollection(TimeSeriesDocument::class)->find());
    }

    private function createTimeSeriesCollection(string $documentClass): void
    {
        $this->dm->getSchemaManager()->createDocumentCollection($documentClass);
        $this->dm->getSchemaManager()->ensureDocumentIndexes($documentClass);
    }
}
