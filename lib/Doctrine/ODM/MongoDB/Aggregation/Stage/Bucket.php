<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use function assert;

class Bucket extends AbstractBucket
{
    /** @var mixed[] */
    private array $boundaries;
    private mixed $default = null;

    /**
     * An array of values based on the groupBy expression that specify the
     * boundaries for each bucket.
     *
     * Each adjacent pair of values acts as the inclusive lower boundary and the
     * exclusive upper boundary for the bucket. You must specify at least two
     * boundaries. The specified values must be in ascending order and all of
     * the same type. The exception is if the values are of mixed numeric types.
     *
     * @param mixed $boundaries
     */
    public function boundaries(...$boundaries): static
    {
        $this->boundaries = $boundaries;

        return $this;
    }

    /**
     * A literal that specifies the _id of an additional bucket that contains
     * all documents whose groupBy expression result does not fall into a bucket
     * specified by boundaries.
     *
     * @param mixed $default
     */
    public function defaultBucket($default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * A document that specifies the fields to include in the output documents
     * in addition to the _id field. To specify the field to include, you must
     * use accumulator expressions.
     */
    public function output(): Bucket\BucketOutput
    {
        if (! $this->output) {
            $this->output = new Bucket\BucketOutput($this->builder, $this);
        }

        assert($this->output instanceof Bucket\BucketOutput);

        return $this->output;
    }

    /** @return array{boundaries: mixed[], default: mixed} */
    protected function getExtraPipelineFields(): array
    {
        $fields = ['boundaries' => $this->boundaries ?? null];
        if ($this->default !== null) {
            $fields['default'] = $this->default;
        }

        return $fields;
    }

    protected function getStageName(): string
    {
        return '$bucket';
    }
}
