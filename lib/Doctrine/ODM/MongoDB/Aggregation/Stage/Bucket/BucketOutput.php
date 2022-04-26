<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

use function assert;

/**
 * Fluent interface for adding an output specification to a bucket stage.
 */
class BucketOutput extends AbstractOutput
{
    public function __construct(Builder $builder, Stage\Bucket $bucket)
    {
        parent::__construct($builder, $bucket);
    }

    /**
     * An expression to group documents by. To specify a field path, prefix the
     * field name with a dollar sign $ and enclose it in quotes.
     *
     * @param mixed|Expr $expression
     *
     * @return Stage\Bucket
     */
    public function groupBy($expression)
    {
        assert($this->bucket instanceof Stage\Bucket);

        return $this->bucket->groupBy($expression);
    }

    /**
     * An array of values based on the groupBy expression that specify the
     * boundaries for each bucket.
     *
     * Each adjacent pair of values acts as the inclusive lower boundary and the
     * exclusive upper boundary for the bucket. You must specify at least two
     * boundaries. The specified values must be in ascending order and all of
     * the same type. The exception is if the values are of mixed numeric types.
     *
     * @param mixed ...$boundaries
     *
     * @return Stage\Bucket
     */
    public function boundaries(...$boundaries)
    {
        assert($this->bucket instanceof Stage\Bucket);

        return $this->bucket->boundaries(...$boundaries);
    }

    /**
     * A literal that specifies the _id of an additional bucket that contains
     * all documents whose groupBy expression result does not fall into a bucket
     * specified by boundaries.
     *
     * @param mixed $default
     *
     * @return Stage\Bucket
     */
    public function defaultBucket($default)
    {
        assert($this->bucket instanceof Stage\Bucket);

        return $this->bucket->defaultBucket($default);
    }
}
