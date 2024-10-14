<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use GeoJson\Geometry\Point;

use function trigger_deprecation;

/**
 * Fluent interface for building aggregation pipelines.
 *
 * @internal
 *
 * @phpstan-import-type PipelineExpression from Builder
 * @phpstan-import-type StageExpression from Builder
 */
abstract class Stage
{
    /** @var Builder */
    protected $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Assembles the aggregation stage
     *
     * @return array<string, mixed>
     * @phpstan-return StageExpression
     */
    abstract public function getExpression(): array;

    /**
     * Executes the aggregation pipeline
     *
     * @deprecated This method was deprecated in doctrine/mongodb-odm 2.2. Please use getAggregation() instead.
     *
     * @param array<string, mixed> $options
     */
    public function execute(array $options = []): Iterator
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.2',
            'Using "%s" is deprecated, use "%s::getAggregation()" instead.',
            __METHOD__,
            self::class,
        );

        return $this->builder->execute($options);
    }

    /**
     * Returns an aggregation object for the current pipeline
     *
     * @param array<string, mixed> $options
     */
    public function getAggregation(array $options = []): Aggregation
    {
        return $this->builder->getAggregation($options);
    }

    /**
     * Adds new fields to documents. $addFields outputs documents that contain
     * all existing fields from the input documents and newly added fields.
     *
     * The $addFields stage is equivalent to a $project stage that explicitly
     * specifies all existing fields in the input documents and adds the new
     * fields.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/addFields/
     */
    public function addFields(): Stage\AddFields
    {
        return $this->builder->addFields();
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
     */
    public function bucket(): Stage\Bucket
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
     */
    public function bucketAuto(): Stage\BucketAuto
    {
        return $this->builder->bucketAuto();
    }

    /**
     * Returns statistics regarding a collection or view.
     *
     * $collStats must be the first stage in an aggregation pipeline, or else
     * the pipeline returns an error.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/
     */
    public function collStats(): Stage\CollStats
    {
        return $this->builder->collStats();
    }

    /**
     * Returns a document that contains a count of the number of documents input
     * to the stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     */
    public function count(string $fieldName): Stage\Count
    {
        return $this->builder->count($fieldName);
    }

    /**
     * Creates new documents in a sequence of documents where certain values in a field are missing.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/densify/
     */
    public function densify(string $fieldName): Stage\Densify
    {
        return $this->builder->densify($fieldName);
    }

    /**
     * Processes multiple aggregation pipelines within a single stage on the
     * same set of input documents.
     *
     * Each sub-pipeline has its own field in the output document where its
     * results are stored as an array of documents.
     */
    public function facet(): Stage\Facet
    {
        return $this->builder->facet();
    }

    /**
     * Populates null and missing field values within documents.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/fill/
     */
    public function fill(): Stage\Fill
    {
        return $this->builder->fill();
    }

    /**
     * Outputs documents in order of nearest to farthest from a specified point.
     * You can only use this as the first stage of a pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function geoNear($x, $y = null): Stage\GeoNear
    {
        return $this->builder->geoNear($x, $y);
    }

    /**
     * Returns the assembled aggregation pipeline
     *
     * @return list<array<string, mixed>>
     * @phpstan-return PipelineExpression
     */
    public function getPipeline(): array
    {
        return $this->builder->getPipeline();
    }

    /**
     * Performs a recursive search on a collection, with options for restricting
     * the search by recursion depth and query filter.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/graphLookup/
     *
     * @param string $from Target collection for the $graphLookup operation to
     * search, recursively matching the connectFromField to the connectToField.
     */
    public function graphLookup(string $from): Stage\GraphLookup
    {
        return $this->builder->graphLookup($from);
    }

    /**
     * Groups documents by some specified expression and outputs to the next
     * stage a document for each distinct grouping.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/group/
     */
    public function group(): Stage\Group
    {
        return $this->builder->group();
    }

    /**
     * Returns statistics regarding the use of each index for the collection.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/
     */
    public function indexStats(): Stage\IndexStats
    {
        return $this->builder->indexStats();
    }

    /**
     * Limits the number of documents passed to the next stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/limit/
     */
    public function limit(int $limit): self
    {
        return $this->builder->limit($limit);
    }

    /**
     * Performs a left outer join to an unsharded collection in the same
     * database to filter in documents from the “joined” collection for
     * processing.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lookup/
     */
    public function lookup(string $from): Stage\Lookup
    {
        return $this->builder->lookup($from);
    }

    /**
     * Filters the documents to pass only the documents that match the specified
     * condition(s) to the next pipeline stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/match/
     */
    public function match(): Stage\MatchStage
    {
        return $this->builder->match();
    }

    /**
     * Writes the results of the aggregation pipeline to a specified collection.
     * The $merge operator must be the last stage in the pipeline.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/merge/
     */
    public function merge(): Stage\Merge
    {
        return $this->builder->merge();
    }

    /**
     * Takes the documents returned by the aggregation pipeline and writes them
     * to a specified collection. This must be the last stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/out/
     */
    public function out(string $collection): Stage\Out
    {
        return $this->builder->out($collection);
    }

    /**
     * Passes along the documents with only the specified fields to the next
     * stage in the pipeline. The specified fields can be existing fields from
     * the input documents or newly computed fields.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/project/
     */
    public function project(): Stage\Project
    {
        return $this->builder->project();
    }

    /**
     * Restricts the contents of the documents based on information stored in
     * the documents themselves.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/redact/
     */
    public function redact(): Stage\Redact
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
     * @param string|mixed[]|null $expression Optional. A replacement expression that
     * resolves to a document.
     */
    public function replaceRoot($expression = null): Stage\ReplaceRoot
    {
        return $this->builder->replaceRoot($expression);
    }

    /**
     * Replaces the input document with the specified document. The operation
     * replaces all existing fields in the input document, including the _id
     * field. With $replaceWith, you can promote an embedded document to the
     * top-level. You can also specify a new document as the replacement.
     *
     * The $replaceWith stage is an alias for $replaceRoot.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/replaceWith/
     *
     * @param string|mixed[]|Expr|null $expression Optional. A replacement expression that
     * resolves to a document.
     */
    public function replaceWith($expression = null): Stage\ReplaceWith
    {
        return $this->builder->replaceWith($expression);
    }

    /**
     * Controls if resulting iterator should be wrapped with CachingIterator.
     */
    public function rewindable(bool $rewindable = true): static
    {
        $this->builder->rewindable($rewindable);

        return $this;
    }

    /**
     * Randomly selects the specified number of documents from its input.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sample/
     */
    public function sample(int $size): Stage\Sample
    {
        return $this->builder->sample($size);
    }

    /**
     * The $search stage performs a full-text search on the specified field or
     * fields which must be covered by an Atlas Search index.
     *
     * @see https://www.mongodb.com/docs/atlas/atlas-search/query-syntax/#mongodb-pipeline-pipe.-search
     */
    public function search(): Stage\Search
    {
        return $this->builder->search();
    }

    /**
     * Adds new fields to documents. $set outputs documents that contain all
     * existing fields from the input documents and newly added fields.
     *
     * The $set stage is an alias for $addFields.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/set/
     */
    public function set(): Stage\Set
    {
        return $this->builder->set();
    }

    /**
     * Performs operations on a specified span of documents in a collection,
     * known as a window, and returns the results based on the chosen window
     * operator.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/setWindowFields/
     */
    public function setWindowFields(): Stage\SetWindowFields
    {
        return $this->builder->setWindowFields();
    }

    /**
     * Skips over the specified number of documents that pass into the stage and
     * passes the remaining documents to the next stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/skip/
     */
    public function skip(int $skip): Stage\Skip
    {
        return $this->builder->skip($skip);
    }

    /**
     * Groups incoming documents based on the value of a specified expression,
     * then computes the count of documents in each distinct group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sortByCount/
     */
    public function sortByCount(string $expression): Stage\SortByCount
    {
        return $this->builder->sortByCount($expression);
    }

    /**
     * Sorts all input documents and returns them to the pipeline in sorted order.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sort/
     *
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     */
    public function sort($fieldName, $order = null): self
    {
        return $this->builder->sort($fieldName, $order);
    }

    /**
     * Performs a union of two collections. $unionWith combines pipeline results
     * from two collections into a single result set. The stage outputs the
     * combined result set (including duplicates) to the next stage.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/unionWith/
     */
    public function unionWith(string $collection): Stage\UnionWith
    {
        return $this->builder->unionWith($collection);
    }

    /**
     * Removes/excludes fields from documents.
     *
     * @see https://www.mongodb.com/docs/rapid/reference/operator/aggregation/unset/
     */
    public function unset(string ...$fields): Stage\UnsetStage
    {
        return $this->builder->unset(...$fields);
    }

    /**
     * Deconstructs an array field from the input documents to output a document
     * for each element. Each output document is the input document with the
     * value of the array field replaced by the element.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/unwind/
     */
    public function unwind(string $fieldName): Stage\Unwind
    {
        return $this->builder->unwind($fieldName);
    }

    /**
     * Allows adding an arbitrary stage to the pipeline
     *
     * @return Stage The method returns the stage given as an argument
     */
    public function addStage(Stage $stage): Stage
    {
        return $this->builder->addStage($stage);
    }
}
