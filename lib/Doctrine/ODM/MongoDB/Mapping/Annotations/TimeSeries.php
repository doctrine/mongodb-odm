<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;

/**
 * Marks a document or superclass as a time series document
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TimeSeries implements Annotation
{
    public function __construct(
        public readonly string $timeField,
        public readonly ?string $metaField = null,
        public readonly ?Granularity $granularity = null,
        public readonly ?int $expireAfterSeconds = null,
        public readonly ?int $bucketMaxSpanSeconds = null,
        public readonly ?int $bucketRoundingSeconds = null,
    ) {
    }
}
