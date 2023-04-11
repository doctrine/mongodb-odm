<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\CompoundedSearchOperatorTrait;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\CompoundSearchOperatorInterface;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\EmbeddedDocument;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\SearchOperator;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\SupportsCompoundableOperatorsTrait;

/** @internal */
class CompoundedEmbeddedDocument extends EmbeddedDocument implements CompoundSearchOperatorInterface
{
    use CompoundedSearchOperatorTrait;
    use SupportsCompoundableOperatorsTrait;

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    protected function addOperator(SearchOperator $operator): SearchOperator
    {
        // Any operator we add should be added to the EmbeddedDocument operator,
        // not the Compound operator we're nested in.
        return EmbeddedDocument::addOperator($operator);
    }
}
