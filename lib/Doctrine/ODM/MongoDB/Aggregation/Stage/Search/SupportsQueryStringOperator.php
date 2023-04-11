<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsQueryStringOperator
{
    public function queryString(string $query = '', string $defaultPath = ''): QueryString;
}
