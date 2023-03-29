<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Closure;

use function array_map;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/compound/
 */
class Compound extends AbstractSearchOperator implements CompoundSearchOperatorInterface, ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;
    use SupportsCompoundableOperatorsTrait;

    /** @var array<string, list<SearchOperator>> */
    private array $operators = [
        'must' => [],
        'mustNot' => [],
        'should' => [],
        'filter' => [],
    ];

    private string $currentClause    = 'must';
    private ?int $minimumShouldMatch = null;

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    protected function addOperator(SearchOperator $operator): SearchOperator
    {
        $this->operators[$this->currentClause][] = $operator;

        return $operator;
    }

    public function must(): static
    {
        $this->currentClause = 'must';

        return $this;
    }

    public function mustNot(): static
    {
        $this->currentClause = 'mustNot';

        return $this;
    }

    public function should(?int $minimumShouldMatch = null): static
    {
        $this->currentClause = 'should';

        if ($minimumShouldMatch !== null) {
            $this->minimumShouldMatch($minimumShouldMatch);
        }

        return $this;
    }

    public function filter(): static
    {
        $this->currentClause = 'filter';

        return $this;
    }

    public function minimumShouldMatch(int $minimumShouldMatch): static
    {
        $this->minimumShouldMatch = $minimumShouldMatch;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'compound';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [];

        foreach ($this->operators as $clause => $operators) {
            if (! $operators) {
                continue;
            }

            $params->$clause = array_map(
                static function (SearchOperator $operator): object {
                    return (object) [
                        $operator->getOperatorName() => $operator->getOperatorParams(),
                    ];
                },
                $operators,
            );
        }

        if ($this->minimumShouldMatch !== null) {
            $params->minimumShouldMatch = $this->minimumShouldMatch;
        }

        return $this->appendScore($params);
    }

    protected function getAddOperatorClosure(): Closure
    {
        return Closure::fromCallable([$this, 'addOperator']);
    }

    protected function getCompoundStage(): Compound
    {
        return $this;
    }
}
