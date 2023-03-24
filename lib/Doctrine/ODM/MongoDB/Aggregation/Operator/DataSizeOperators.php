<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all data size aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface DataSizeOperators
{
    /**
     * Returns the size of a given string or binary data value's content in bytes.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/binarySize/
     *
     * @param mixed|Expr $expression
     */
    public function binarySize($expression): static;

    /**
     * Returns the size in bytes of a given document (i.e. bsontype Object) when encoded as BSON.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bsonSize/
     *
     * @param mixed|Expr $expression
     */
    public function bsonSize($expression): static;
}
