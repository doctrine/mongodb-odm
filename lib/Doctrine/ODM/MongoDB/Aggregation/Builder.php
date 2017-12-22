<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;
use GeoJson\Geometry\Point;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;

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
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * @var string
     */
    private $hydrationClass;

    /**
     * The Collection instance.
     *
     * @var Collection
     */
    private $collection;

    /**
     * @var Stage[]
     */
    private $stages = [];

    /**
     * Create a new aggregation builder.
     *
     * @param DocumentManager $dm
     * @param string $documentName
     */
    public function __construct(DocumentManager $dm, $documentName)
    {
        $this->dm = $dm;
        $this->class = $this->dm->getClassMetadata($documentName);
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
     *
     * @return Stage\AddFields
     */
    public function addFields()
    {
        return $this->addStage(new Stage\AddFields($this));
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
        return $this->addStage(new Stage\Bucket($this, $this->dm, $this->class));
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
        return $this->addStage(new Stage\BucketAuto($this, $this->dm, $this->class));
    }

    /**
     * Returns statistics regarding a collection or view.
     *
     * $collStats must be the first stage in an aggregation pipeline, or else
     * the pipeline returns an error.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/collStats/
     * @since 1.5
     *
     * @return Stage\CollStats
     */
    public function collStats()
    {
        return $this->addStage(new Stage\CollStats($this));
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
        return $this->addStage(new Stage\Count($this, $fieldName));
    }

    /**
     * Executes the aggregation pipeline
     *
     * @param array $options
     * @return Iterator
     */
    public function execute($options = [])
    {
        // Force cursor to be used
        $options = array_merge($options, ['cursor' => true]);

        $cursor = $this->collection->aggregate($this->getPipeline(), $options);

        return $this->prepareIterator($cursor);
    }

    /**
     * @return Expr
     */
    public function expr()
    {
        return new Expr($this->dm, $this->class);
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
        return $this->addStage(new Stage\Facet($this));
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
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/geoNear/
     *
     * @param float|array|Point $x
     * @param float $y
     * @return Stage\GeoNear
     */
    public function geoNear($x, $y = null)
    {
        return $this->addStage(new Stage\GeoNear($this, $x, $y));
    }

    /**
     * Returns the assembled aggregation pipeline
     *
     * For pipelines where the first stage is a $geoNear stage, it will apply
     * the document filters and discriminator queries to the query portion of
     * the geoNear operation. For all other pipelines, it prepends a $match stage
     * containing the required query.
     *
     * @return array
     */
    public function getPipeline()
    {
        $pipeline = array_map(
            function (Stage $stage) { return $stage->getExpression(); },
            $this->stages
        );

        if ($this->getStage(0) instanceof Stage\GeoNear) {
            $pipeline[0]['$geoNear']['query'] = $this->applyFilters($pipeline[0]['$geoNear']['query']);
        } else {
            $matchExpression = $this->applyFilters([]);
            if ($matchExpression !== []) {
                array_unshift($pipeline, ['$match' => $matchExpression]);
            }
        }

        return $pipeline;
    }

    /**
     * Returns a certain stage from the pipeline
     *
     * @param integer $index
     * @return Stage
     */
    public function getStage($index)
    {
        if ( ! isset($this->stages[$index])) {
            throw new \OutOfRangeException("Could not find stage with index {$index}.");
        }

        return $this->stages[$index];
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
        return $this->addStage(new Stage\GraphLookup($this, $from, $this->dm, $this->class));
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
        return $this->addStage(new Stage\Group($this));
    }

    /**
     * Set which class to use when hydrating results as document class instances.
     *
     * @param string $className
     *
     * @return self
     */
    public function hydrate($className)
    {
        $this->hydrationClass = $className;

        return $this;
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
        return $this->addStage(new Stage\IndexStats($this));
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
        return $this->addStage(new Stage\Limit($this, $limit));
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
        return $this->addStage(new Stage\Lookup($this, $from, $this->dm, $this->class));
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
        return $this->addStage(new Stage\Match($this));
    }

    /**
     * Returns a query expression to be used in match stages
     *
     * @return QueryExpr
     */
    public function matchExpr()
    {
        $expr = new QueryExpr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
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
    public function out($from)
    {
        return $this->addStage(new Stage\Out($this, $from, $this->dm));
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
        return $this->addStage(new Stage\Project($this));
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
        return $this->addStage(new Stage\Redact($this));
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
        return $this->addStage(new Stage\ReplaceRoot($this, $this->dm, $this->class, $expression));
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
        return $this->addStage(new Stage\Sample($this, $size));
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
        return $this->addStage(new Stage\Skip($this, $skip));
    }

    /**
     * Sorts all input documents and returns them to the pipeline in sorted
     * order.
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
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];
        // fixme: move to sort stage
        return $this->addStage(new Stage\Sort($this, $this->getDocumentPersister()->prepareSort($fields)));
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
        return $this->addStage(new Stage\SortByCount($this, $expression, $this->dm, $this->class));
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
        // Fixme: move field name translation to stage
        return $this->addStage(new Stage\Unwind($this, $this->getDocumentPersister()->prepareFieldName($fieldName)));
    }

    /**
     * @param Stage $stage
     * @return Stage
     */
    protected function addStage(Stage $stage)
    {
        $this->stages[] = $stage;

        return $stage;
    }

    /**
     * Applies filters and discriminator queries to the pipeline
     *
     * @param array $query
     * @return array
     */
    private function applyFilters(array $query)
    {
        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);

        $query = $documentPersister->addDiscriminatorToPreparedQuery($query);
        $query = $documentPersister->addFilterToPreparedQuery($query);

        return $query;
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }

    /**
     * @param Cursor $cursor
     *
     * @return Iterator
     */
    private function prepareIterator(Cursor $cursor): Iterator
    {
        $class = null;
        if ($this->hydrationClass) {
            $class = $this->dm->getClassMetadata($this->hydrationClass);
        }

        if ($class) {
            $cursor = new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $class);
        }

        return new CachingIterator($cursor);
    }
}
