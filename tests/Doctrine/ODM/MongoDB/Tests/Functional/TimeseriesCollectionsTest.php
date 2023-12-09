<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class TimeseriesCollectionsTest extends BaseTestCase
{
    public function testCreateTimeseriesCollectionsCapped(): void
    {
        $sm = $this->dm->getSchemaManager();
        $sm->dropDocumentCollection(TimeseriesCollectionTestDocument::class);
        $sm->createDocumentCollection(TimeseriesCollectionTestDocument::class);

        $coll                = $this->dm->getDocumentCollection(TimeseriesCollectionTestDocument::class);
        $document            = new TimeseriesCollectionTestDocument();
        $document->createdAt = new DateTime('2023-12-09 00:00:00');
        $document->metaData  = ['foo' => 'bar'];
        $document->value     = 1337;
        $this->dm->persist($document);
        $this->dm->flush();

        $data = $coll->find()->toArray();
        self::assertCount(1, $data);
    }
}

/**
 * @ODM\Document(collection={
 *   "name"="TimeseriesCollectionTest",
 *   "capped"=true,
 *   "size"=1000,
 *   "max"=1,
 *   "timeseries"={
 *     "timeField"="createdAt",
 *     "metaField"="metaData",
 *     "granularity"="seconds",
 *  },
 *     "expireAfterSeconds"=60
 * })
 */
class TimeseriesCollectionTestDocument
{
    /** @ODM\Id */
    public ?string $id;

    /** @ODM\Field(type="date") */
    public ?DateTimeInterface $createdAt;

    /**
     * @ODM\Field(type="hash")
     *
     * @var array|null
     */
    public ?array $metaData;

    /** @ODM\Field(type="integer") */
    public ?int $value;
}
