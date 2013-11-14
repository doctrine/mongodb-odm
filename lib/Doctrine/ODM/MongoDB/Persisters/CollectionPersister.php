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

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * The CollectionPersister is responsible for persisting collections of embedded
 * or referenced documents. When a PersistentCollection is scheduledForDeletion
 * in the UnitOfWork by calling PersistentCollection::clear() or is
 * de-referenced in the domain application code, CollectionPersister::delete()
 * will be called. When documents within the PersistentCollection are added or
 * removed, CollectionPersister::update() will be called, which may set the
 * entire collection or delete/insert individual elements, depending on the
 * mapping strategy.
 *
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
     * @var DocumentManager
     */
    private $dm;

    /**
     * The PersistenceBuilder instance.
     *
     * @var PersistenceBuilder
     */
    private $pb;

    /**
     * Constructs a new CollectionPersister instance.
     *
     * @param DocumentManager $dm
     * @param PersistenceBuilder $pb
     * @param UnitOfWork $uow
     */
    public function __construct(DocumentManager $dm, PersistenceBuilder $pb, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->pb = $pb;
        $this->uow = $uow;
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
        $query = array('$unset' => array($propertyPath => true));
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Updates a PersistentCollection instance deleting removed rows and
     * inserting new rows.
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

        switch ($mapping['strategy']) {
            case 'set':
            case 'setArray':
                $this->setCollection($coll, $options);
                break;

            case 'addToSet':
            case 'pushAll':
                $this->deleteElements($coll, $options);
                $this->insertElements($coll, $options);
                break;

            default:
                throw new \UnexpectedValueException('Unsupported collection strategy: ' . $mapping['strategy']);
        }
    }

    /**
     * Sets a PersistentCollection instance.
     *
     * This method is intended to be used with the "set" or "setArray"
     * strategies. The "setArray" strategy will ensure that the collection is
     * set as a BSON array, which means the collection elements will be
     * reindexed numerically before storage.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    private function setCollection(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $pb = $this->pb;

        $callback = isset($mapping['embedded'])
            ? function($v) use ($pb, $mapping) { return $pb->prepareEmbeddedDocumentValue($mapping, $v); }
            : function($v) use ($pb, $mapping) { return $pb->prepareReferencedDocumentValue($mapping, $v); };

        $setData = $coll->map($callback)->toArray();

        if ($mapping['strategy'] === 'setArray') {
            $setData = array_values($setData);
        }

        $query = array('$set' => array($propertyPath => $setData));

        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Deletes removed elements from a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    private function deleteElements(PersistentCollection $coll, array $options)
    {
        $deleteDiff = $coll->getDeleteDiff();

        if (empty($deleteDiff)) {
            return;
        }

        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $query = array('$unset' => array());

        foreach ($deleteDiff as $key => $document) {
            $query['$unset'][$propertyPath . '.' . $key] = true;
        }

        $this->executeQuery($parent, $query, $options);

        /**
         * @todo This is a hack right now because we don't have a proper way to
         * remove an element from an array by its key. Unsetting the key results
         * in the element being left in the array as null so we have to pull
         * null values.
         */
        $this->executeQuery($parent, array('$pull' => array($propertyPath => null)), $options);
    }

    /**
     * Inserts new elements for a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     *
     * @param PersistentCollection $coll
     * @param array $options
     */
    private function insertElements(PersistentCollection $coll, array $options)
    {
        $insertDiff = $coll->getInsertDiff();

        if (empty($insertDiff)) {
            return;
        }

        $mapping = $coll->getMapping();
        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $pb = $this->pb;

        $callback = isset($mapping['embedded'])
            ? function($v) use ($pb, $mapping) { return $pb->prepareEmbeddedDocumentValue($mapping, $v); }
            : function($v) use ($pb, $mapping) { return $pb->prepareReferencedDocumentValue($mapping, $v); };

        $value = array_values(array_map($callback, $insertDiff));

        if ($mapping['strategy'] !== 'pushAll') {
            $value = array('$each' => $value);
        }

        $query = array('$' . $mapping['strategy'] => array($propertyPath => $value));

        $this->executeQuery($parent, $query, $options);
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
     * Gets the parent information for a given PersistentCollection. It will
     * retrieve the top-level persistent Document that the PersistentCollection
     * lives in. We can use this to issue queries when updating a
     * PersistentCollection that is multiple levels deep inside an embedded
     * document.
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
            $path = $propertyPath . '.' . $path;
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
