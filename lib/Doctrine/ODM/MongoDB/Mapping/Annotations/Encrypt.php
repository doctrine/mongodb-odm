<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use DateTimeInterface;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;
use MongoDB\BSON\UTCDateTime;

/**
 * Defines an encrypted field mapping.
 *
 * @see https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/encrypt-and-query/#configure-encrypted-fields-for-optimal-search-and-storage
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Encrypt implements Annotation
{
    public int|float|Int64|Decimal128|UTCDateTime|null $min;
    public int|float|Int64|Decimal128|UTCDateTime|null $max;

    /**
     * @param EncryptQuery|null $queryType  Set the query type for the field, null if not queryable.
     * @param int<1, 4>|null    $sparsity
     * @param positive-int|null $precision
     * @param positive-int|null $trimFactor
     * @param positive-int|null $contention
     */
    public function __construct(
        public ?EncryptQuery $queryType = null,
        int|float|Int64|Decimal128|UTCDateTime|DateTimeInterface|null $min = null,
        int|float|Int64|Decimal128|UTCDateTime|DateTimeInterface|null $max = null,
        public ?int $sparsity = null,
        public ?int $precision = null,
        public ?int $trimFactor = null,
        public ?int $contention = null,
    ) {
        $this->min = $min instanceof DateTimeInterface ? new UTCDateTime($min) : $min;
        $this->max = $max instanceof DateTimeInterface ? new UTCDateTime($max) : $max;
    }
}
