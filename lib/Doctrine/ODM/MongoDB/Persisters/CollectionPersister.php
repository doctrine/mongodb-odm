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

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * The CollectionPersister is responsible for persisting collections of embedded documents
 * or referenced documents. When a PersistentCollection is scheduledForDeletion in the UnitOfWork
 * by calling PersistentCollection::clear() or is de-referenced in the domain application
 * code it results in a CollectionPersister::delete(). When a single document is removed
 * from a PersitentCollection it is removed in the call to CollectionPersister::deleteRows()
 * and new documents added to the PersistentCollection are inserted in the call to
 * CollectionPersister::insertRows().
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Bulat Shakirzyanov <bulat@theopenskyproject.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class CollectionPersister
{
    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The PersistenceBuilder instance.
     *
     * @var Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder
     */
    private $pb;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * Contructs a new CollectionPersister instance.
     *
     * @param DocumentManager $dm
     * @param PersistenceBuilder $pb
     * @param UnitOfWork $uow
     * @param string $cmd
     */
    public function __construct(DocumentManager $dm, PersistenceBuilder $pb, UnitOfWork $uow, $cmd)
    {
        $this->dm = $dm;
        $this->pb = $pb;
        $this->uow = $uow;
        $this->cmd = $cmd;
    }

    /**
     * Deletes a PersistentCollection instance completely from a document using $unset.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    public function delete(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        $query = array($this->cmd . 'unset' => array($propertyPath => true));
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Updates a PersistentCollection instance deleting removed rows and inserting new rows.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    public function update(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }
        $this->deleteRows($coll, $options);
        $this->insertRows($coll, $options);
    }

    /**
     * Deletes removed rows from a PersistentCollection instance.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    private function deleteRows(PersistentCollection $coll, array $options)
    {
        $deleteDiff = $coll->getDeleteDiff();
        if ($deleteDiff) {
            list($propertyPath, $parent) = $this->getPathAndParent($coll);
            $query = array($this->cmd.'unset' => array());
            foreach ($deleteDiff as $key => $document) {
                $query[$this->cmd.'unset'][$propertyPath.'.'.$key] = true;
            }
            $this->executeQuery($parent, $query, $options);

            /**
             * @todo This is a hack right now because we don't have a proper way to remove
             * an element from an array by its key. Unsetting the key results in the element
             * being left in the array as null so we have to pull null values.
             *
             * "Using "$unset" with an expression like this "array.$" will result in the array item becoming null, not being removed. You can issue an update with "{$pull:{x:null}}" to remove all nulls."
             * http://www.mongodb.org/display/DOCS/Updating#Updating-%24unset
             */
            $mapping = $coll->getMapping();
            if ($mapping['strategy'] !== 'set') {
                $this->executeQuery($parent, array($this->cmd.'pull' => array($propertyPath => null)), $options);
            }
        }
    }

    /**
     * Inserts new rows for a PersistentCollection instance.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    private function insertRows(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        if ($mapping['strategy'] === 'set') {
            $setData = array();
            foreach ($coll as $key => $document) {
                if (isset($mapping['reference'])) {
                    $setData[$key] = $this->pb->prepareReferencedDocumentValue($mapping, $document);
                } else {
                    $setData[$key] = $this->pb->prepareEmbeddedDocumentValue($mapping, $document);
                }
            }
            $query = array($this->cmd.'set' => array($propertyPath => $setData));
            $this->executeQuery($parent, $query, $options);
        } else {
            $strategy = isset($mapping['strategy']) ? $mapping['strategy'] : 'pushAll';
            $insertDiff = $coll->getInsertDiff();
            if ($insertDiff) {
                $query = array($this->cmd.$strategy => array());
                foreach ($insertDiff as $document) {
                    if (isset($mapping['reference'])) {
                        $query[$this->cmd.$strategy][$propertyPath][] = $this->pb->prepareReferencedDocumentValue($mapping, $document);
                    } else {
                        $query[$this->cmd.$strategy][$propertyPath][] = $this->pb->prepareEmbeddedDocumentValue($mapping, $document);
                    }
                }
                $this->executeQuery($parent, $query, $options);
            }
        }
    }

    /**
     * Gets the document database identifier value for the given document.
     *
     * @param object $document
     * @param ClassMetadata $class
     * @return mixed $id
     */
    private function getDocumentId($document, ClassMetadata $class)
    {
        return $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
    }

    /**
     * Gets the parent information for a given PersistentCollection. It will retrieve the top
     * level persistent @Document that the PersistentCollection lives in. We can use this to issue
     * queries when updating a PersistentCollection that is multiple levels deep inside an
     * embedded document.
     *
     *     <code>
     *     list($path, $parent) = $this->getPathAndParent($coll)
     *     </code>
     *
     * @param PersistentCollection $coll
     * @return array $pathAndParent
     */
    private function getPathAndParent(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $fields = array();
        $parent = $coll->getOwner();
        while (null !== ($association = $this->uow->getParentAssociation($parent))) {
            list($m, $owner, $field) = $association;
            if (isset($m['reference'])) {
                break;
            }
            $parent = $owner;
            $fields[] = $field;
        }
        $propertyPath = implode('.', array_reverse($fields));
        $path = $mapping['name'];
        if ($propertyPath) {
            $path = $propertyPath.'.'.$path;
        }
        return array($path, $parent);
    }

    /**
     * Executes a query updating the given document.
     *
     * @param object $document
     * @param array $query
     * @param array $options
     */
    private function executeQuery($document, array $query, array $options)
    {
        $className = get_class($document);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
        $collection = $this->dm->getDocumentCollection($className);
        $collection->update(array('_id' => $id), $query, $options);
    }
}