<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

use function array_values;

/**
 * Fluent interface for adding a $densify stage to an aggregation pipeline.
 */
class Densify extends Stage
{
    private string $field;

    /** @var array<string> */
    private array $partitionByFields = [];

    private ?object $range = null;

    public function __construct(Builder $builder, string $fieldName)
    {
        parent::__construct($builder);

        $this->field = $fieldName;
    }

    public function partitionByFields(string ...$fields): self
    {
        $this->partitionByFields = array_values($fields);

        return $this;
    }

    /**
     * @param array{0: int, 1: int}|string $bounds
     * @param int|float                    $step
     */
    public function range($bounds, $step, string $unit = ''): self
    {
        $this->range = (object) [
            'bounds' => $bounds,
            'step' => $step,
        ];

        if ($unit !== '') {
            $this->range->unit = $unit;
        }

        return $this;
    }

    public function getExpression(): array
    {
        $params = (object) ['field' => $this->field];

        if ($this->partitionByFields) {
            $params->partitionByFields = $this->partitionByFields;
        }

        if ($this->range) {
            $params->range = $this->range;
        }

        return ['$densify' => $params];
    }
}
