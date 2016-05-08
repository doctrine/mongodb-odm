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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Query builder for ODM.
 *
 * @since       1.0
 */
class Builder extends \Doctrine\MongoDB\Query\Builder
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
     * The current field we are operating on.
     *
     * @todo Change this to private once ODM requires doctrine/mongodb 1.1+
     * @var string
     */
    protected $currentField;

    /**
     * Whether or not to hydrate the data to documents.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     *
     * @var boolean
     */
    private $refresh = false;

    /**
     * Array of primer Closure instances.
     *
     * @var array
     */
    private $primers = array();

    /**
     * Whether or not to require indexes.
     *
     * @var bool
     */
    private $requireIndexes;

    /**
     * Whether or not to register documents in UnitOfWork.
     *
     * @var bool
     */
    private $readOnly;

    /**
     * Construct a Builder
     *
     * @param DocumentManager $dm
     * @param string[]|string|null $documentName (optional) an array of document names, the document name, or none
     */
    public function __construct(DocumentManager $dm, $documentName = null)
    {
        $this->dm = $dm;
        $this->expr = new Expr($dm);
        if ($documentName !== null) {
            $this->setDocumentName($documentName);
        }
    }

    /**
     * Set whether or not to require indexes.
     *
     * @param bool $requireIndexes
     * @return $this
     */
    public function requireIndexes($requireIndexes = true)
    {
        $this->requireIndexes = $requireIndexes;
        return $this;
    }

    /**
     * Set the current field to operate on.
     *
     * @param string $field
     * @return $this
     */
    public function field($field)
    {
        $this->currentField = $field;
        parent::field($field);

        return $this;
    }

    /**
     * Use a primer to eagerly load all references in the current field.
     *
     * If $primer is true or a callable is provided, referenced documents for
     * this field will loaded into UnitOfWork immediately after the query is
     * executed. This will avoid multiple queries due to lazy initialization of
     * Proxy objects.
     *
     * If $primer is false, no priming will take place. That is also the default
     * behavior.
     *
     * If a custom callable is used, its signature should conform to the default
     * Closure defined in {@link ReferencePrimer::__construct()}.
     *
     * @param boolean|callable $primer
     * @return $this
     * @throws \InvalidArgumentException If $primer is not boolean or callable
     */
    public function prime($primer = true)
    {
        if ( ! is_bool($primer) && ! is_callable($primer)) {
            throw new \InvalidArgumentException('$primer is not a boolean or callable');
        }

        if ($primer === false) {
            unset($this->primers[$this->currentField]);

            return $this;
        }

        if (array_key_exists('eagerCursor', $this->query) && !$this->query['eagerCursor']) {
            throw new \BadMethodCallException("Can't call prime() when setting eagerCursor to false");
        }

        $this->primers[$this->currentField] = $primer;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function eagerCursor($bool = true)
    {
        if ( ! $bool && ! empty($this->primers)) {
            throw new \BadMethodCallException("Can't set eagerCursor to false when using reference primers");
        }

        return parent::eagerCursor($bool);
    }


    /**
     * @param bool $bool
     * @return $this
     */
    public function hydrate($bool = true)
    {
        $this->hydrate = $bool;
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function readOnly($bool = true)
    {
        $this->readOnly = $bool;
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function refresh($bool = true)
    {
        $this->refresh = $bool;
        return $this;
    }

    /**
     * Change the query type to find and optionally set and change the class being queried.
     *
     * @param string $documentName
     * @return $this
     */
    public function find($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::find();

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function findAndUpdate($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::findAndUpdate();

        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function returnNew($bool = true)
    {
        $this->refresh(true);
        parent::returnNew($bool);

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function findAndRemove($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::findAndRemove();

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function update($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::update();

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function insert($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::insert();

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function remove($documentName = null)
    {
        $this->setDocumentName($documentName);
        parent::remove();

        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function references($document)
    {
        $this->expr->references($document);
        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function includesReferenceTo($document)
    {
        $this->expr->includesReferenceTo($document);
        return $this;
    }

    /**
     * Gets the Query executable.
     *
     * @param array $options
     * @return Query $query
     */
    public function getQuery(array $options = array())
    {
        if ($this->query['type'] === Query::TYPE_MAP_REDUCE) {
            $this->hydrate = false;
        }

        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);

        $query = $this->query;

        $query['query'] = $this->expr->getQuery();
        $query['query'] = $documentPersister->addDiscriminatorToPreparedQuery($query['query']);
        $query['query'] = $documentPersister->addFilterToPreparedQuery($query['query']);

        $query['newObj'] = $this->expr->getNewObj();

        if (isset($query['distinct'])) {
            $query['distinct'] = $documentPersister->prepareFieldName($query['distinct']);
        }

        if ($this->class->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION && ! empty($query['upsert']) &&
            (empty($query['query'][$this->class->discriminatorField]) || is_array($query['query'][$this->class->discriminatorField]))) {
            throw new \InvalidArgumentException('Upsert query that is to be performed on discriminated document does not have single ' .
                'discriminator. Either not use base class or set \'' . $this->class->discriminatorField . '\' field manually.');
        }

        if ( ! empty($query['select'])) {
            $query['select'] = $documentPersister->prepareSortOrProjection($query['select']);
            if ($this->hydrate && $this->class->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION
                && ! isset($query['select'][$this->class->discriminatorField])) {
                $includeMode = 0 < count(array_filter($query['select'], function($mode) { return $mode == 1; }));
                if ($includeMode && ! isset($query['select'][$this->class->discriminatorField])) {
                    $query['select'][$this->class->discriminatorField] = 1;
                }
            }
        }

        if (isset($query['sort'])) {
            $query['sort'] = $documentPersister->prepareSortOrProjection($query['sort']);
        }

        if ($this->class->slaveOkay) {
            $query['slaveOkay'] = $this->class->slaveOkay;
        }

        return new Query(
            $this->dm,
            $this->class,
            $this->collection,
            $query,
            $options,
            $this->hydrate,
            $this->refresh,
            $this->primers,
            $this->requireIndexes,
            $this->readOnly
        );
    }

    /**
     * Create a new Expr instance that can be used as an expression with the Builder
     *
     * @return Expr $expr
     */
    public function expr()
    {
        $expr = new Expr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * @param string[]|string $documentName an array of document names or just one.
     */
    private function setDocumentName($documentName)
    {
        if (is_array($documentName)) {
            $documentNames = $documentName;
            $documentName = $documentNames[0];

            $metadata = $this->dm->getClassMetadata($documentName);
            $discriminatorField = $metadata->discriminatorField;
            $discriminatorValues = $this->getDiscriminatorValues($documentNames);

            // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
            if ($metadata->defaultDiscriminatorValue && array_search($metadata->defaultDiscriminatorValue, $discriminatorValues) !== false) {
                $discriminatorValues[] = null;
            }

            $this->field($discriminatorField)->in($discriminatorValues);
        }

        if ($documentName !== null) {
            $this->collection = $this->dm->getDocumentCollection($documentName);
            $this->class = $this->dm->getClassMetadata($documentName);

            // Expr also needs to know
            $this->expr->setClassMetadata($this->class);
        }
    }

    /**
     * Get Discriminator Values
     *
     * @param \Iterator|array $classNames
     * @return array an array of discriminatorValues (mixed type)
     * @throws \InvalidArgumentException if the number of found collections > 1
     */
    private function getDiscriminatorValues($classNames)
    {
        $discriminatorValues = array();
        $collections = array();
        foreach ($classNames as $className) {
            $class = $this->dm->getClassMetadata($className);
            $discriminatorValues[] = $class->discriminatorValue;
            $key = $this->dm->getDocumentDatabase($className)->getName() . '.' . $class->getCollection();
            $collections[$key] = $key;
        }
        if (count($collections) > 1) {
            throw new \InvalidArgumentException('Documents involved are not all mapped to the same database collection.');
        }
        return $discriminatorValues;
    }
}
