<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use MongoDB\BSON\UTCDateTime;

use function array_values;

/**
 * Fluent interface for adding a $densify stage to an aggregation pipeline.
 *
 * @phpstan-type BoundsType 'full'|'partition'|array{0: int|float|UTCDateTime, 1: int|float|UTCDateTime}
 * @phpstan-type UnitType 'year'|'month'|'week'|'day'|'hour'|'minute'|'second'|'millisecond'
 * @phpstan-type DensifyStageExpression array{
 *     '$densify': object{
 *         field: string,
 *         partitionByFields?: list<string>,
 *         range: object{
 *             bounds?: BoundsType,
 *             step?: int|float,
 *             unit?: UnitType
 *         }
 *     }
 * }
 */
class Densify extends Stage
{
    private string $field;

    /** @var array<string> */
    private array $partitionByFields = [];

    private object $range;

    public function __construct(Builder $builder, string $fieldName)
    {
        parent::__construct($builder);

        $this->field = $fieldName;
        $this->range = (object) [];
    }

    public function partitionByFields(string ...$fields): static
    {
        $this->partitionByFields = array_values($fields);

        return $this;
    }

    /**
     * @param array|string $bounds
     * @param int|float    $step
     * @phpstan-param BoundsType  $bounds
     * @phpstan-param ''|UnitType $unit
     */
    public function range($bounds, $step, string $unit = ''): static
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

    /** @phpstan-return DensifyStageExpression */
    public function getExpression(): array
    {
        $params = (object) [
            'field' => $this->field,
            'range' => $this->range,
        ];

        if ($this->partitionByFields) {
            $params->partitionByFields = $this->partitionByFields;
        }

        return ['$densify' => $params];
    }
}
