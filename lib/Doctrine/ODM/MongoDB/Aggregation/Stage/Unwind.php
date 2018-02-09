<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $unwind stage to an aggregation pipeline.
 *
 */
class Unwind extends Stage
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $includeArrayIndex;

    /**
     * @var bool
     */
    private $preserveNullAndEmptyArrays = false;

    /**
     * @param string $fieldName
     */
    public function __construct(Builder $builder, $fieldName)
    {
        parent::__construct($builder);

        $this->fieldName = (string) $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        // Fallback behavior for MongoDB < 3.2
        if ($this->includeArrayIndex === null && ! $this->preserveNullAndEmptyArrays) {
            return [
                '$unwind' => $this->fieldName,
            ];
        }

        $unwind = ['path' => $this->fieldName];

        foreach (['includeArrayIndex', 'preserveNullAndEmptyArrays'] as $option) {
            if (! $this->$option) {
                continue;
            }

            $unwind[$option] = $this->$option;
        }

        return ['$unwind' => $unwind];
    }

    /**
     * The name of a new field to hold the array index of the element. The name
     * cannot start with a dollar sign $.
     *
     * @param string $includeArrayIndex
     * @return $this
     *
     */
    public function includeArrayIndex($includeArrayIndex)
    {
        $this->includeArrayIndex = $includeArrayIndex;

        return $this;
    }

    /**
     * If true, if the path is null, missing, or an empty array, $unwind outputs
     * the document.
     *
     * @param bool $preserveNullAndEmptyArrays
     * @return $this
     *
     */
    public function preserveNullAndEmptyArrays($preserveNullAndEmptyArrays = true)
    {
        $this->preserveNullAndEmptyArrays = $preserveNullAndEmptyArrays;

        return $this;
    }
}
