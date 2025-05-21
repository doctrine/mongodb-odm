<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use MongoDB\BSON\Type;

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
    /**
     * @param EncryptQuery|null $queryType  Set the query type for the field, null if not queryable.
     * @param int<1, 4>|null    $sparsity
     * @param positive-int|null $prevision
     * @param positive-int|null $trimFactor
     * @param positive-int|null $contention
     */
    public function __construct(
        public ?EncryptQuery $queryType = null,
        public string|int|Type|null $min = null,
        public string|int|Type|null $max = null,
        public ?int $sparsity = null,
        public ?int $prevision = null,
        public ?int $trimFactor = null,
        public ?int $contention = null,
    ) {
    }
}
