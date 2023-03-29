<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsCompoundableSearchOperators extends SupportsAutocompleteOperator, SupportsEmbeddedDocumentOperator, SupportsEqualsOperator, SupportsExistsOperator, SupportsGeoShapeOperator, SupportsGeoWithinOperator, SupportsMoreLikeThisOperator, SupportsNearOperator, SupportsPhraseOperator, SupportsQueryStringOperator, SupportsRangeOperator, SupportsRegexOperator, SupportsTextOperator, SupportsWildcardOperator
{
}
