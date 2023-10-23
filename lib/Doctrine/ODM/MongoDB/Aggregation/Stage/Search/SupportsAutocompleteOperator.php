<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsAutocompleteOperator
{
    public function autocomplete(string $path = '', string ...$query): Autocomplete;
}
