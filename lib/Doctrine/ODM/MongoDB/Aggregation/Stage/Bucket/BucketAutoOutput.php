<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding an output specification to a bucket stage.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class BucketAutoOutput extends AbstractOutput
{
    /**
     * @param Builder $builder
     * @param Stage\BucketAuto $bucket
     */
    public function __construct(Builder $builder, Stage\BucketAuto $bucket)
    {
        parent::__construct($builder, $bucket);
    }

    /**
     * An expression to group documents by. To specify a field path, prefix the
     * field name with a dollar sign $ and enclose it in quotes.
     *
     * @return Stage\BucketAuto
     */
    public function groupBy($expression)
    {
        return $this->bucket->groupBy($expression);
    }

    /**
     * A positive 32-bit integer that specifies the number of buckets into which input documents are grouped.
     *
     * @param int $buckets
     *
     * @return Stage\BucketAuto
     */
    public function buckets($buckets)
    {
        return $this->bucket->buckets($buckets);
    }

    /**
     * A string that specifies the preferred number series to use to ensure that
     * the calculated boundary edges end on preferred round numbers or their
     * powers of 10.
     *
     * @param string $granularity
     *
     * @return Stage\BucketAuto
     */
    public function granularity($granularity)
    {
        return $this->bucket->granularity($granularity);
    }
}
