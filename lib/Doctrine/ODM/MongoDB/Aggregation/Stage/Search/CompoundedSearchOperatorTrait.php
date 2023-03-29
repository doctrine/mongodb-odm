<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Closure;
use LogicException;

use function sprintf;

/** @internal */
trait CompoundedSearchOperatorTrait
{
    public function __construct(private Compound $compound, private Closure $addOperator, ...$args)
    {
        if (! $this instanceof CompoundSearchOperatorInterface) {
            throw new LogicException(sprintf('Can only use %s on classes extending %s.', self::class, CompoundSearchOperatorInterface::class));
        }

        parent::__construct(...$args);
    }

    public function must(): Compound
    {
        return $this->compound->must();
    }

    public function mustNot(): Compound
    {
        return $this->compound->mustNot();
    }

    public function should(?int $minimumShouldMatch = null): Compound
    {
        return $this->compound->should($minimumShouldMatch);
    }

    public function filter(): Compound
    {
        return $this->compound->filter();
    }

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    protected function addOperator(SearchOperator $operator): SearchOperator
    {
        return $this->getAddOperatorClosure()($operator);
    }

    protected function getAddOperatorClosure(): Closure
    {
        return $this->addOperator;
    }

    protected function getCompoundStage(): Compound
    {
        return $this->compound;
    }
}
