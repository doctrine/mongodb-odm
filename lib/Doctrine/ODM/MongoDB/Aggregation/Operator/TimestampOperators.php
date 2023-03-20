<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

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
     * @param mixed|Expr $expression
     */
    public function tsIncrement($expression): self;

    /**
     * Returns the seconds from a timestamp as a long.
     *
     * @param mixed|Expr $expression
     */
    public function tsSecond($expression): self;
}
