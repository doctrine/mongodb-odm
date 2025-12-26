<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

/**
 * Interface containing timestamp aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface TimestampOperators
{
    /**
     * Returns the incrementing ordinal from a timestamp as a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tsIncrement/
     */
    public function tsIncrement(mixed $expression): static;

    /**
     * Returns the seconds from a timestamp as a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tsSecond/
     */
    public function tsSecond(mixed $expression): static;
}
