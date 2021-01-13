<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;
use GeoJson\Geometry\Point;
use MongoDB\Collection;
use OutOfRangeException;
use TypeError;

use function array_map;
use function array_unshift;
use function func_get_arg;
use function func_num_args;
use function gettype;
use function is_array;
use function is_bool;
use function sprintf;
use function trigger_deprecation;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Builder
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance.
     *
     * @var ClassMetadata
     */
    private $class;

    /** @var string */
    private $hydrationClass;

    /**
     * The Collection instance.
     *
     * @var Collection
     */
    private $collection;

    /** @var Stage[] */
    private $stages = [];

    /** @var bool */
    private $rewindable = true;

    /**
     * Create a new aggregation builder.
     */
    public function __construct(DocumentManager $dm, string $documentName)
    {
        $this->dm         = $dm;
        $this->class      = $this->dm->getClassMetadata($documentName);
        $this->collection = $this->dm->getDocumentCollection($documentName);
    }

    /**
     * Adds new fields to documents. $addFields outputs documents that contain all
     * existing fields from the input documents and newly added fields.
     *
     * The $addFields stage is equivalent to a $project stage that explicitly specifies
     * all existing fields in the input documents and adds the new fields.
     *
     * If the name of the new field is the same as an existing field name (including _id),
     * $addFields overwrites the existing value of that field with the value of the
     * specified expression.
     *
     * @see http://docs.mongodb.com/manual/reference/operator/aggregation/addFields/
     */
    public function addFields(): Stage\AddFields
    {
        $stage = new Stage\AddFields($this);
        $this->addStage($stage);

        return $stage;
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
        $stage = new Stage\Bucket($this, $this->dm, $this->class);
        $this->addStage($stage);

        return $stage;
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
        $stage = new Stage\BucketAuto($this, $this->dm, $this->class);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Returns statistics regarding a collection or view.
     *
     * $collStats must be the first stage in an aggregation pipeline, or else
     * the pipeline returns an error.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/collStats/
     */
    public function collStats(): Stage\CollStats
    {
        $stage = new Stage\CollStats($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Returns a document that contains a count of the number of documents input
     * to the stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     */
    public function count(string $fieldName): Stage\Count
    {
        $stage = new Stage\Count($this, $fieldName);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Executes the aggregation pipeline
     *
     * @deprecated This method was deprecated in doctrine/mongodb-odm 2.2. Please use getAggregation() instead.
     */
    public function execute(array $options = []): Iterator
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.2',
            'Using "%s" is deprecated. Please use "%s::getAggregation()" instead.',
            __METHOD__,
            self::class
        );

        return $this->getAggregation($options)->getIterator();
    }

    public function expr(): Expr
    {
        return new Expr($this->dm, $this->class);
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
        $stage = new Stage\Facet($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Outputs documents in order of nearest to farthest from a specified point.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation. If GeoJSON is
     * used, the "spherical" option will default to true.
     *
     * You can only use this as the first stage of a pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/
     *
     * @param float|array|Point $x
     * @param float             $y
     */
    public function geoNear($x, $y = null): Stage\GeoNear
    {
        $stage = new Stage\GeoNear($this, $x, $y);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Returns an aggregation object for the current pipeline
     */
    public function getAggregation(array $options = []): Aggregation
    {
        $class = $this->hydrationClass ? $this->dm->getClassMetadata($this->hydrationClass) : null;

        return new Aggregation($this->dm, $class, $this->collection, $this->getPipeline(), $options, $this->rewindable);
    }

    // phpcs:disable Squiz.Commenting.FunctionComment.ExtraParamComment
    /**
     * Returns the assembled aggregation pipeline
     *
     * @param bool $applyFilters Whether to apply filters on the aggregation
     * pipeline stage
     *
     * For pipelines where the first stage is a $geoNear stage, it will apply
     * the document filters and discriminator queries to the query portion of
     * the geoNear operation. For all other pipelines, it prepends a $match stage
     * containing the required query.
     *
     * For aggregation pipelines that will be nested (e.g. in a facet stage),
     * you should not apply filters as this may cause wrong results to be
     * given.
     */
    // phpcs:enable Squiz.Commenting.FunctionComment.ExtraParamComment
    public function getPipeline(/* bool $applyFilters = true */): array
    {
        $applyFilters = func_num_args() > 0 ? func_get_arg(0) : true;

        if (! is_bool($applyFilters)) {
            throw new TypeError(sprintf(
                'Argument 1 passed to %s must be of the type bool, %s given',
                __METHOD__,
                gettype($applyFilters)
            ));
        }

        $pipeline = array_map(
            static function (Stage $stage) {
                return $stage->getExpression();
            },
            $this->stages
        );

        if ($this->getStage(0) instanceof Stage\IndexStats) {
            // Don't apply any filters when using an IndexStats stage: since it
            // needs to be the first pipeline stage, prepending a match stage
            // with discriminator information will not work

            $applyFilters = false;
        }

        if (! $applyFilters) {
            return $pipeline;
        }

        if ($this->getStage(0) instanceof Stage\GeoNear) {
            $pipeline[0]['$geoNear']['query'] = $this->applyFilters($pipeline[0]['$geoNear']['query']);

            return $pipeline;
        }

        $matchExpression = $this->applyFilters([]);
        if ($matchExpression !== []) {
            array_unshift($pipeline, ['$match' => $matchExpression]);
        }

        return $pipeline;
    }

    /**
     * Returns a certain stage from the pipeline
     */
    public function getStage(int $index): Stage
    {
        if (! isset($this->stages[$index])) {
            throw new OutOfRangeException(sprintf('Could not find stage with index %d.', $index));
        }

        return $this->stages[$index];
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
        $stage = new Stage\GraphLookup($this, $from, $this->dm, $this->class);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Groups documents by some specified expression and outputs to the next
     * stage a document for each distinct grouping.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/group/
     */
    public function group(): Stage\Group
    {
        $stage = new Stage\Group($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Set which class to use when hydrating results as document class instances.
     */
    public function hydrate(string $className): self
    {
        $this->hydrationClass = $className;

        return $this;
    }

    /**
     * Returns statistics regarding the use of each index for the collection.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/
     */
    public function indexStats(): Stage\IndexStats
    {
        $stage = new Stage\IndexStats($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Limits the number of documents passed to the next stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/limit/
     */
    public function limit(int $limit): Stage\Limit
    {
        $stage = new Stage\Limit($this, $limit);
        $this->addStage($stage);

        return $stage;
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
        $stage = new Stage\Lookup($this, $from, $this->dm, $this->class);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Filters the documents to pass only the documents that match the specified
     * condition(s) to the next pipeline stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/match/
     */
    public function match(): Stage\MatchStage
    {
        $stage = new Stage\MatchStage($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Returns a query expression to be used in match stages
     */
    public function matchExpr(): QueryExpr
    {
        $expr = new QueryExpr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * Takes the documents returned by the aggregation pipeline and writes them
     * to a specified collection. This must be the last stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/out/
     */
    public function out(string $from): Stage\Out
    {
        $stage = new Stage\Out($this, $from, $this->dm);
        $this->addStage($stage);

        return $stage;
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
        $stage = new Stage\Project($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Restricts the contents of the documents based on information stored in
     * the documents themselves.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/redact/
     */
    public function redact(): Stage\Redact
    {
        $stage = new Stage\Redact($this);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Promotes a specified document to the top level and replaces all other
     * fields.
     *
     * The operation replaces all existing fields in the input document,
     * including the _id field. You can promote an existing embedded document to
     * the top level, or create a new document for promotion.
     *
     * @param string|array|null $expression Optional. A replacement expression that
     * resolves to a document.
     */
    public function replaceRoot($expression = null): Stage\ReplaceRoot
    {
        $stage = new Stage\ReplaceRoot($this, $this->dm, $this->class, $expression);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Controls if resulting iterator should be wrapped with CachingIterator.
     */
    public function rewindable(bool $rewindable = true): self
    {
        $this->rewindable = $rewindable;

        return $this;
    }

    /**
     * Randomly selects the specified number of documents from its input.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sample/
     */
    public function sample(int $size): Stage\Sample
    {
        $stage = new Stage\Sample($this, $size);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Skips over the specified number of documents that pass into the stage and
     * passes the remaining documents to the next stage in the pipeline.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/skip/
     */
    public function skip(int $skip): Stage\Skip
    {
        $stage = new Stage\Skip($this, $skip);
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Sorts all input documents and returns them to the pipeline in sorted
     * order.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sort/
     *
     * @param array|string $fieldName Field name or array of field/order pairs
     * @param int|string   $order     Field order (if one field is specified)
     */
    public function sort($fieldName, $order = null): Stage\Sort
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];
        // fixme: move to sort stage
        $stage = new Stage\Sort($this, $this->getDocumentPersister()->prepareSort($fields));
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Groups incoming documents based on the value of a specified expression,
     * then computes the count of documents in each distinct group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sortByCount/
     */
    public function sortByCount(string $expression): Stage\SortByCount
    {
        $stage = new Stage\SortByCount($this, $expression, $this->dm, $this->class);
        $this->addStage($stage);

        return $stage;
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
        // Fixme: move field name translation to stage
        $stage = new Stage\Unwind($this, $this->getDocumentPersister()->prepareFieldName($fieldName));
        $this->addStage($stage);

        return $stage;
    }

    /**
     * Allows adding an arbitrary stage to the pipeline
     *
     * @return Stage The method returns the stage given as an argument
     */
    public function addStage(Stage $stage): Stage
    {
        $this->stages[] = $stage;

        return $stage;
    }

    /**
     * Applies filters and discriminator queries to the pipeline
     */
    private function applyFilters(array $query): array
    {
        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);

        $query = $documentPersister->addDiscriminatorToPreparedQuery($query);
        $query = $documentPersister->addFilterToPreparedQuery($query);

        return $query;
    }

    private function getDocumentPersister(): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
