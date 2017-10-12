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

use Doctrine\MongoDB\Aggregation\Builder as BaseBuilder;
use Doctrine\MongoDB\Aggregation\Stage\GeoNear;
use Doctrine\MongoDB\CommandCursor as BaseCommandCursor;
use Doctrine\ODM\MongoDB\CommandCursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Builder extends BaseBuilder
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
     * Create a new aggregation builder.
     *
     * @param DocumentManager $dm
     * @param string $documentName
     */
    public function __construct(DocumentManager $dm, $documentName)
    {
        $this->dm = $dm;
        $this->class = $this->dm->getClassMetadata($documentName);

        parent::__construct($this->dm->getDocumentCollection($documentName));
    }

    /**
     * {@inheritdoc}
     */
    public function execute($options = [])
    {
        // Force cursor to be used
        $options = array_merge($options, ['cursor' => true]);

        $cursor = parent::execute($options);

        return $this->prepareCursor($cursor);
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
     * @return QueryExpr
     */
    public function matchExpr()
    {
        $expr = new QueryExpr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * @return Expr
     */
    public function expr()
    {
        return new Expr($this->dm, $this->class);
    }

    /**
     * @return Stage\Bucket
     */
    public function bucket()
    {
        return $this->addStage(new Stage\Bucket($this, $this->dm, $this->class));
    }

    /**
     * @return Stage\BucketAuto
     */
    public function bucketAuto()
    {
        return $this->addStage(new Stage\BucketAuto($this, $this->dm, $this->class));
    }

    /**
     * @param string $from
     *
     * @return Stage\GraphLookup
     */
    public function graphLookup($from)
    {
        return $this->addStage(new Stage\GraphLookup($this, $from, $this->dm, $this->class));
    }

    /**
     * @return Stage\Match
     */
    public function match()
    {
        return $this->addStage(new Stage\Match($this));
    }

    /**
     * @param string $from
     * @return Stage\Lookup
     */
    public function lookup($from)
    {
        return $this->addStage(new Stage\Lookup($this, $from, $this->dm, $this->class));
    }

    /**
     * @param string $from
     * @return Stage\Out
     */
    public function out($from)
    {
        return $this->addStage(new Stage\Out($this, $from, $this->dm));
    }

    /**
     * @param string|null $expression Optional. A replacement expression that
     * resolves to a document.
     * @return Stage\ReplaceRoot
     */
    public function replaceRoot($expression = null)
    {
        return $this->addStage(new Stage\ReplaceRoot($this, $this->dm, $this->class, $expression));
    }

    /**
     * @return Stage\SortByCount
     */
    public function sortByCount($expression)
    {
        return $this->addStage(new Stage\SortByCount($this, $expression, $this->dm, $this->class));
    }

    /**
     * {@inheritdoc}
     */
    public function sort($fieldName, $order = null)
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];
        return parent::sort($this->getDocumentPersister()->prepareSortOrProjection($fields));
    }

    /**
     * {@inheritdoc}
     */
    public function unwind($fieldName)
    {
        $fieldName = $this->getDocumentPersister()->prepareFieldName($fieldName);
        return parent::unwind($fieldName);
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
        $pipeline = parent::getPipeline();

        if ($this->getStage(0) instanceof GeoNear) {
            $pipeline[0]['$geoNear']['query'] = $this->applyFilters($pipeline[0]['$geoNear']['query']);
        } else {
            $matchStage = $this->applyFilters([]);
            if ($matchStage !== []) {
                array_unshift($pipeline, ['$match' => $matchStage]);
            }
        }

        return $pipeline;
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
     * @param BaseCommandCursor $cursor
     *
     * @return CommandCursor
     */
    private function prepareCursor(BaseCommandCursor $cursor)
    {
        $class = null;
        if ($this->hydrationClass) {
            $class = $this->dm->getClassMetadata($this->hydrationClass);
        }

        return new CommandCursor($cursor, $this->dm->getUnitOfWork(), $class);
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
