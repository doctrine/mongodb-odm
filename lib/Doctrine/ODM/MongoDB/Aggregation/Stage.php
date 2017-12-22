<?php

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use GeoJson\Geometry\Point;

/**
 * Fluent interface for building aggregation pipelines.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
 * @internal
 */
abstract class Stage
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Assembles the aggregation stage
     *
     * @return array
     */
    abstract public function getExpression();

    /**
     * Executes the aggregation pipeline
     *
     * @param array $options
     * @return Iterator
     */
    public function execute($options = [])
    {
        return $this->builder->execute($options);
    }

    /**
     * Categorizes incoming documents into groups, called buckets, based on a
     * specified expression and bucket boundaries.
     *
     * Each bucket is represented as a document in the output. The document for
     * each bucket contains an _id field, whose value specifies the inclusive
     * lower bound of the bucket and a count field that contains the number of
     * documents in the bucket. The count field is included by default when the
     * output is not specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bucket/
     *
     * @return Stage\Bucket
     */
    public function bucket()
    {
        return $this->builder->bucket();
    }

    /**
     * Categorizes incoming documents into a specific number of groups, called
     * buckets, based on a specified expression.
     *
     * Bucket boundaries are automatically determined in an attempt to evenly
     * distribute the documents into the specified number of buckets. Each
     * bucket is represented as a document in the output. The document for each
     * bucket contains an _id field, whose value specifies the inclusive lower
     * bound and the exclusive upper bound for the bucket, and a count field
     * that contains the number of documents in the bucket. The count field is
     * included by default when the output is not specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bucketAuto/
     *
     * @return Stage\BucketAuto
     */
    public function bucketAuto()
    {
        return $this->builder->bucketAuto();
    }

    /**
     * Returns statistics regarding a collection or view.
     *
     * $collStats must be the first stage in an aggregation pipeline, or else
     * the pipeline returns an error.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/geoNear/
     * @since 1.5
     *
     * @return Stage\CollStats
     */
    public function collStats()
    {
        return $this->builder->collStats();
    }

    /**
     * Returns a document that contains a count of the number of documents input
     * to the stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     *
     * @return Stage\Count
     */
    public function count($fieldName)
    {
        return $this->builder->count($fieldName);
    }

    /**
     * Processes multiple aggregation pipelines within a single stage on the
     * same set of input documents.
     *
     * Each sub-pipeline has its own field in the output document where its
     * results are stored as an array of documents.
     *
     * @return Stage\Facet
     */
    public function facet()
    {
        return $this->builder->facet();
    }

    /**
     * Outputs documents in order of nearest to farthest from a specified point.
     * You can only use this as the first stage of a pipeline.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/geoNear/
     *
     * @param float|array|Point $x
     * @param float $y
     * @return Stage\GeoNear
     */
    public function geoNear($x, $y = null)
    {
        return $this->builder->geoNear($x, $y);
    }

    /**
     * Returns the assembled aggregation pipeline
     *
     * @return array
     */
    public function getPipeline()
    {
        return $this->builder->getPipeline();
    }

    /**
     * Performs a recursive search on a collection, with options for restricting
     * the search by recursion depth and query filter.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/graphLookup/
     *
     * @param string $from Target collection for the $graphLookup operation to
     * search, recursively matching the connectFromField to the connectToField.
     * @return Stage\GraphLookup
     */
    public function graphLookup($from)
    {
        return $this->builder->graphLookup($from);
    }

    /**
     * Groups documents by some specified expression and outputs to the next
     * stage a document for each distinct grouping.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/group/
     *
     * @return Stage\Group
     */
    public function group()
    {
        return $this->builder->group();
    }

    /**
     * Returns statistics regarding the use of each index for the collection.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/indexStats/
     *
     * @return Stage\IndexStats
     */
    public function indexStats()
    {
        return $this->builder->indexStats();
    }

    /**
     * Limits the number of documents passed to the next stage in the pipeline.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/limit/
     *
     * @param integer $limit
     * @return Stage\Limit
     */
    public function limit($limit)
    {
        return $this->builder->limit($limit);
    }

    /**
     * Performs a left outer join to an unsharded collection in the same
     * database to filter in documents from the “joined” collection for
     * processing.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/lookup/
     *
     * @param string $from
     * @return Stage\Lookup
     */
    public function lookup($from)
    {
        return $this->builder->lookup($from);
    }

    /**
     * Filters the documents to pass only the documents that match the specified
     * condition(s) to the next pipeline stage.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/match/
     *
     * @return Stage\Match
     */
    public function match()
    {
        return $this->builder->match();
    }

    /**
     * Takes the documents returned by the aggregation pipeline and writes them
     * to a specified collection. This must be the last stage in the pipeline.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/out/
     *
     * @param string $collection
     * @return Stage\Out
     */
    public function out($collection)
    {
        return $this->builder->out($collection);
    }

    /**
     * Passes along the documents with only the specified fields to the next
     * stage in the pipeline. The specified fields can be existing fields from
     * the input documents or newly computed fields.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/project/
     *
     * @return Stage\Project
     */
    public function project()
    {
        return $this->builder->project();
    }

    /**
     * Restricts the contents of the documents based on information stored in
     * the documents themselves.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/redact/
     *
     * @return Stage\Redact
     */
    public function redact()
    {
        return $this->builder->redact();
    }

    /**
     * Promotes a specified document to the top level and replaces all other
     * fields.
     *
     * The operation replaces all existing fields in the input document,
     * including the _id field. You can promote an existing embedded document to
     * the top level, or create a new document for promotion.
     *
     * @param string|null $expression Optional. A replacement expression that
     * resolves to a document.
     *
     * @return Stage\ReplaceRoot
     */
    public function replaceRoot($expression = null)
    {
        return $this->builder->replaceRoot($expression);
    }

    /**
     * Randomly selects the specified number of documents from its input.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/sample/
     *
     * @param integer $size
     * @return Stage\Sample
     */
    public function sample($size)
    {
        return $this->builder->sample($size);
    }

    /**
     * Skips over the specified number of documents that pass into the stage and
     * passes the remaining documents to the next stage in the pipeline.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/skip/
     *
     * @param integer $skip
     * @return Stage\Skip
     */
    public function skip($skip)
    {
        return $this->builder->skip($skip);
    }

    /**
     * Groups incoming documents based on the value of a specified expression,
     * then computes the count of documents in each distinct group.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/sortByCount/
     *
     * @param string $expression The expression to group by
     * @return Stage\SortByCount
     */
    public function sortByCount($expression)
    {
        return $this->builder->sortByCount($expression);
    }

    /**
     * Sorts all input documents and returns them to the pipeline in sorted order.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/sort/
     *
     * @param array|string $fieldName Field name or array of field/order pairs
     * @param integer|string $order   Field order (if one field is specified)
     * @return Stage\Sort
     */
    public function sort($fieldName, $order = null)
    {
        return $this->builder->sort($fieldName, $order);
    }

    /**
     * Deconstructs an array field from the input documents to output a document
     * for each element. Each output document is the input document with the
     * value of the array field replaced by the element.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/unwind/
     *
     * @param string $fieldName The field to unwind. It is automatically prefixed with the $ sign
     * @return Stage\Unwind
     */
    public function unwind($fieldName)
    {
        return $this->builder->unwind($fieldName);
    }
}
