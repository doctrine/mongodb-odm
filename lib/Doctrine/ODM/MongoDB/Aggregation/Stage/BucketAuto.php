<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use function assert;

/**
 * @method BucketAuto groupBy($expression)
 */
class BucketAuto extends AbstractBucket
{
    /** @var int */
    private $buckets;

    /** @var string */
    private $granularity;

    /**
     * A positive 32-bit integer that specifies the number of buckets into which
     * input documents are grouped.
     */
    public function buckets(int $buckets): self
    {
        $this->buckets = $buckets;

        return $this;
    }

    /**
     * A string that specifies the preferred number series to use to ensure that
     * the calculated boundary edges end on preferred round numbers or their
     * powers of 10.
     */
    public function granularity(string $granularity): self
    {
        $this->granularity = $granularity;

        return $this;
    }

    /**
     * A document that specifies the fields to include in the output documents
     * in addition to the _id field. To specify the field to include, you must
     * use accumulator expressions.
     */
    public function output(): Bucket\BucketAutoOutput
    {
        if (! $this->output) {
            $this->output = new Bucket\BucketAutoOutput($this->builder, $this);
        }

        assert($this->output instanceof Bucket\BucketAutoOutput);

        return $this->output;
    }

    protected function getExtraPipelineFields(): array
    {
        $fields = ['buckets' => $this->buckets];
        if ($this->granularity !== null) {
            $fields['granularity'] = $this->granularity;
        }

        return $fields;
    }

    protected function getStageName(): string
    {
        return '$bucketAuto';
    }
}
