<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;

/**
 * Marks a document or superclass as a time series document
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class TimeSeries implements MappingAttribute
{
    public function __construct(
        public string $timeField,
        public ?string $metaField = null,
        public ?Granularity $granularity = null,
        public ?int $expireAfterSeconds = null,
        public ?int $bucketMaxSpanSeconds = null,
        public ?int $bucketRoundingSeconds = null,
    ) {
    }
}
