<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

interface SupportsEqualsOperator
{
    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function equals(string $path = '', $value = null): Equals;
}
