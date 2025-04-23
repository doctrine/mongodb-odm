<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use MongoDB\BSON\Type;
use MongoDB\Driver\ClientEncryption;

/**
 * Defines an index on a field
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Encrypt
{
    /**
     * @link https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/encrypt-and-query/#configure-encrypted-fields-for-optimal-search-and-storage
     *
     * @param ClientEncryption::QUERY_TYPE_* $queryType
     * @param int<1, 4>|null                 $sparsity
     * @param positive-int|null              $prevision
     * @param positive-int|null              $trimFactor
     * @param positive-int|null              $contention
     */
    public function __construct(
        public ?string $bsonType = null, // Should be extracted from the field type
        public ?string $queryType = null,
        public string|int|Type|null $min = null,
        public string|int|Type|null $max = null,
        public ?int $sparsity = null,
        public ?int $prevision = null,
        public ?int $trimFactor = null,
        public ?int $contention = null,
    ) {
    }
}
