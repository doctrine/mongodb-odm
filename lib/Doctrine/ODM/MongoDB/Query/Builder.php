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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Hydrator,
    Doctrine\ODM\MongoDB\Query\Expr,
    Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Query builder for ODM.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
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
     * @var ClassMetadata
     */
    private $class;

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

    public function __construct(DocumentManager $dm, $cmd, $documentName = null)
    {
        $this->dm   = $dm;
        $this->expr = new Expr($dm, $cmd);
        $this->cmd  = $cmd;
        if ($documentName !== null) {
            $this->setDocumentName($documentName);
        }
    }

    /**
     * @param bool $bool
     * @return Builder
     */
    public function hydrate($bool = true)
    {
        $this->hydrate = $bool;
        return $this;
    }

    /**
     * @param bool $bool
     * @return Builder
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
     * @return Builder
     */
    public function find($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::find();
    }

    /**
     * @param string $documentName
     * @return Builder
     */
    public function findAndUpdate($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::findAndUpdate();
    }

    /**
     * @param string $documentName
     * @return Builder
     */
    public function findAndRemove($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::findAndRemove();
    }

    /**
     * @param string $documentName
     * @return Builder
     */
    public function update($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::update();
    }

    /**
     * @param string $documentName
     * @return Builder
     */
    public function insert($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::insert();
    }

    /**
     * @param string $documentName
     * @return Builder
     */
    public function remove($documentName = null)
    {
        $this->setDocumentName($documentName);
        return parent::remove();
    }

    /**
     * @param $document
     * @return Builder
     */
    public function references($document)
    {
        $this->expr->references($document);
        return $this;
    }

    /**
     * @param $document
     * @return Builder
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
     * @return QueryInterface $query
     */
    public function getQuery(array $options = array())
    {
        if ($this->query['type'] === Query::TYPE_MAP_REDUCE) {
            $this->hydrate = false;
        }

        $query = $this->query;

        $query['query'] = $this->expr->getQuery();
        $query['newObj'] = $this->expr->getNewObj();

        return new Query(
            $this->dm,
            $this->class,
            $this->database,
            $this->collection,
            $query,
            $options,
            $this->cmd,
            $this->hydrate,
            $this->refresh
        );
    }

    /**
     * Create a new Query\Expr instance that can be used as an expression with the QueryBuilder
     *
     * @return Query\Expr $expr
     */
    public function expr()
    {
        $expr = new Expr($this->dm, $this->cmd);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    private function setDocumentName($documentName)
    {
        if (is_array($documentName)) {
            $documentNames = $documentName;
            $documentName = $documentNames[0];

            $discriminatorField = $this->dm->getClassMetadata($documentName)->discriminatorField['name'];
            $discriminatorValues = $this->getDiscriminatorValues($documentNames);
            $this->field($discriminatorField)->in($discriminatorValues);
        }

        if ($documentName !== null) {
            $this->collection = $this->dm->getDocumentCollection($documentName);
            $this->database = $this->collection->getDatabase();
            $this->class = $this->dm->getClassMetadata($documentName);

            // Expr also needs to know
            $this->expr->setClassMetadata($this->class);
        }
    }

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