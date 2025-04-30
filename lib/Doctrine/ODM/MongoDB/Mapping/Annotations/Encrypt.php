<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use InvalidArgumentException;
use MongoDB\BSON\Type;
use MongoDB\Driver\ClientEncryption;

use function in_array;

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
    public const QUERY_TYPE_EQUALITY = ClientEncryption::QUERY_TYPE_EQUALITY;
    public const QUERY_TYPE_RANGE    = ClientEncryption::QUERY_TYPE_RANGE;

    /**
     * @param self::QUERY_TYPE_*|null $queryType  Set the query type for the field, null if not queryable.
     * @param int<1, 4>|null          $sparsity
     * @param positive-int|null       $prevision
     * @param positive-int|null       $trimFactor
     * @param positive-int|null       $contention
     */
    public function __construct(
        public ?string $queryType = null,
        public string|int|Type|null $min = null,
        public string|int|Type|null $max = null,
        public ?int $sparsity = null,
        public ?int $prevision = null,
        public ?int $trimFactor = null,
        public ?int $contention = null,
    ) {
        if ($this->queryType && ! in_array($this->queryType, [self::QUERY_TYPE_EQUALITY, self::QUERY_TYPE_RANGE], true)) {
            throw new InvalidArgumentException('Invalid query type');
        }
    }
}
