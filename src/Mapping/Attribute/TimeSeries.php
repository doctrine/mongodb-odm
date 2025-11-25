<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;

use function class_alias;

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

// @phpstan-ignore class.notFound
class_alias(TimeSeries::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\TimeSeries::class);
