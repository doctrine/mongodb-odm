<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $unwind stage to an aggregation pipeline.
 *
 * @phpstan-type UnwindStageExpression array{
 *     '$unwind': string|array{
 *         path: string,
 *         includeArrayIndex?: string,
 *         preserveNullAndEmptyArrays?: bool,
 *     }
 * }
 */
class Unwind extends Stage
{
    private ?string $includeArrayIndex = null;

    private bool $preserveNullAndEmptyArrays = false;

    public function __construct(Builder $builder, private string $fieldName)
    {
        parent::__construct($builder);
    }

    /** @phpstan-return UnwindStageExpression */
    public function getExpression(): array
    {
        // Fallback behavior for MongoDB < 3.2
        if ($this->includeArrayIndex === null && ! $this->preserveNullAndEmptyArrays) {
            return [
                '$unwind' => $this->fieldName,
            ];
        }

        $unwind = ['path' => $this->fieldName];

        if ($this->includeArrayIndex) {
            $unwind['includeArrayIndex'] = $this->includeArrayIndex;
        }

        if ($this->preserveNullAndEmptyArrays) {
            $unwind['preserveNullAndEmptyArrays'] = $this->preserveNullAndEmptyArrays;
        }

        return ['$unwind' => $unwind];
    }

    /**
     * The name of a new field to hold the array index of the element. The name
     * cannot start with a dollar sign $.
     */
    public function includeArrayIndex(string $includeArrayIndex): static
    {
        $this->includeArrayIndex = $includeArrayIndex;

        return $this;
    }

    /**
     * If true, if the path is null, missing, or an empty array, $unwind outputs
     * the document.
     */
    public function preserveNullAndEmptyArrays(bool $preserveNullAndEmptyArrays = true): static
    {
        $this->preserveNullAndEmptyArrays = $preserveNullAndEmptyArrays;

        return $this;
    }
}
