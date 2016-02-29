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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor;

/**
 * A DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @since       1.0
 */
class DocumentRepository implements ObjectRepository, Selectable
{
    /**
     * @var string
     */
    protected $documentName;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * @var ClassMetadata
     */
    protected $class;

    /**
     * Initializes this instance with the specified document manager, unit of work and
     * class metadata.
     *
     * @param DocumentManager $dm The DocumentManager to use.
     * @param UnitOfWork $uow The UnitOfWork to use.
     * @param ClassMetadata $classMetadata The class metadata.
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $classMetadata)
    {
        $this->documentName = $classMetadata->name;
        $this->dm = $dm;
        $this->uow = $uow;
        $this->class = $classMetadata;
    }

    /**
     * Creates a new Query\Builder instance that is preconfigured for this document name.
     *
     * @return Query\Builder $qb
     */
    public function createQueryBuilder()
    {
        return $this->dm->createQueryBuilder($this->documentName);
    }

    /**
     * Clears the repository, causing all managed documents to become detached.
     */
    public function clear()
    {
        $this->dm->clear($this->class->rootDocumentName);
    }

    /**
     * Finds a document matching the specified identifier. Optionally a lock mode and
     * expected version may be specified.
     *
     * @param mixed $id Identifier.
     * @param int $lockMode Optional. Lock mode; one of the LockMode constants.
     * @param int $lockVersion Optional. Expected version.
     * @throws Mapping\MappingException
     * @throws LockException
     * @return object|null The document, if found, otherwise null.
     */
    public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        if ($id === null) {
            return null;
        }

        /* TODO: What if the ID object has a field with the same name as the
         * class' mapped identifier field name?
         */
        if (is_array($id)) {
            list($identifierFieldName) = $this->class->getIdentifierFieldNames();

            if (isset($id[$identifierFieldName])) {
                $id = $id[$identifierFieldName];
            }
        }

        // Check identity map first
        if ($document = $this->uow->tryGetById($id, $this->class)) {
            if ($lockMode !== LockMode::NONE) {
                $this->dm->lock($document, $lockMode, $lockVersion);
            }

            return $document; // Hit!
        }

        $criteria = array('_id' => $id);

        if ($lockMode === LockMode::NONE) {
            return $this->getDocumentPersister()->load($criteria);
        }

        if ($lockMode === LockMode::OPTIMISTIC) {
            if (!$this->class->isVersioned) {
                throw LockException::notVersioned($this->documentName);
            }
            if ($document = $this->getDocumentPersister()->load($criteria)) {
                $this->uow->lock($document, $lockMode, $lockVersion);
            }

            return $document;
        }

        return $this->getDocumentPersister()->load($criteria, null, array(), $lockMode);
    }

    /**
     * Finds all documents in the repository.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->findBy(array());
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array        $criteria Query criteria
     * @param array        $sort     Sort array for Cursor::sort()
     * @param integer|null $limit    Limit for Cursor::limit()
     * @param integer|null $skip     Skip for Cursor::skip()
     *
     * @return array
     */
    public function findBy(array $criteria, array $sort = null, $limit = null, $skip = null)
    {
        return $this->getDocumentPersister()->loadAll($criteria, $sort, $limit, $skip)->toArray(false);
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array $criteria
     * @return object
     */
    public function findOneBy(array $criteria)
    {
        return $this->getDocumentPersister()->load($criteria);
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array $arguments
     * @throws MongoDBException
     * @throws \BadMethodCallException If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     * @return array|object The found document/documents.
     */
    public function __call($method, $arguments)
    {
        if (strpos($method, 'findBy') === 0) {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } elseif (strpos($method, 'findOneBy') === 0) {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        } else {
            throw new \BadMethodCallException(
                "Undefined method: '$method'. The method name must start with 'findBy' or 'findOneBy'!"
            );
        }

        if (!isset($arguments[0])) {
            throw MongoDBException::findByRequiresParameter($method . $by);
        }

        $fieldName = Inflector::camelize($by);

        if ($this->class->hasField($fieldName)) {
            return $this->$method(array($fieldName => $arguments[0]));
        } else {
            throw MongoDBException::invalidFindByCall($this->documentName, $fieldName, $method . $by);
        }
    }

    /**
     * @return string
     */
    public function getDocumentName()
    {
        return $this->documentName;
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->getDocumentName();
    }

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     *
     * @see Selectable::matching()
     * @param Criteria $criteria
     * @return Collection
     */
    public function matching(Criteria $criteria)
    {
        $visitor = new QueryExpressionVisitor($this->createQueryBuilder());
        $queryBuilder = $this->createQueryBuilder();

        if ($criteria->getWhereExpression() !== null) {
            $expr = $visitor->dispatch($criteria->getWhereExpression());
            $queryBuilder->setQueryArray($expr->getQuery());
        }

        if ($criteria->getMaxResults() !== null) {
            $queryBuilder->limit($criteria->getMaxResults());
        }

        if ($criteria->getFirstResult() !== null) {
            $queryBuilder->skip($criteria->getFirstResult());
        }

        if ($criteria->getOrderings() !== null) {
            $queryBuilder->sort($criteria->getOrderings());
        }

        // @TODO: wrap around a specialized Collection for efficient count on large collections
        return new ArrayCollection($queryBuilder->getQuery()->execute()->toArray(false));
    }

    protected function getDocumentPersister()
    {
        return $this->uow->getDocumentPersister($this->documentName);
    }
}
