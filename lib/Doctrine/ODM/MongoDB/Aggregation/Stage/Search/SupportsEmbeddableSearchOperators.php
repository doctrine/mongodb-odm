<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsEmbeddableSearchOperators extends SupportsAutocompleteOperator, SupportsCompoundOperator, SupportsEqualsOperator, SupportsExistsOperator, SupportsGeoShapeOperator, SupportsGeoWithinOperator, SupportsNearOperator, SupportsPhraseOperator, SupportsQueryStringOperator, SupportsRangeOperator, SupportsRegexOperator, SupportsTextOperator, SupportsWildcardOperator
{
}
