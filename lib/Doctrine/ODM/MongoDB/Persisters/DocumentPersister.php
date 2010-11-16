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

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\MongoCursor,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\ODMEvents,
    Doctrine\ODM\MongoDB\Event\OnUpdatePreparedArgs,
    Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * The DocumentPersister is responsible for actual persisting the calculated
 * changesets performed by the UnitOfWork.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class DocumentPersister
{
    /**
     * The DataPreparer instance.
     *
     * @var Doctrine\ODM\MongoDB\Persisters\DataPreparer
     */
    private $dp;

    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

    /**
     * The ClassMetadata instance for the document type being persisted.
     *
     * @var Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * The MongoCollection instance for this document.
     *
     * @var Doctrine\ODM\MongoDB\MongoCollection
     */
    private $collection;

    /**
     * The string document name being persisted.
     *
     * @var string
     */
    private $documentName;

    /**
     * Array of quered inserts for the persister to insert.
     *
     * @var array
     */
    private $queuedInserts = array();

    /**
     * Documents to be updated, used in executeReferenceUpdates() method
     * @var array
     */
    private $documentsToUpdate = array();

    /**
     * Fields to update, used in executeReferenceUpdates() method
     * @var array
     */
    private $fieldsToUpdate = array();

    /**
     * Mongo command prefix
     * @var string
     */
    private $cmd;

    /**
     * Initializes a new DocumentPersister instance.
     *
     * @param Doctrine\ODM\MongoDB\Persisters\DataPreparer $dp
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     */
    public function __construct(DataPreparer $dp, DocumentManager $dm, ClassMetadata $class)
    {
        $this->dp = $dp;
        $this->dm = $dm;
        $this->uow = $dm->getUnitOfWork();
        $this->class = $class;
        $this->documentName = $class->getName();
        $this->collection = $dm->getDocumentCollection($class->name);
        $this->cmd = $this->dm->getConfiguration()->getMongoCmd();
    }

    /**
     * Adds a document to the queued insertions.
     * The document remains queued until {@link executeInserts} is invoked.
     *
     * @param object $document The document to queue for insertion.
     */
    public function addInsert($document)
    {
        $this->queuedInserts[spl_object_hash($document)] = $document;
    }

    /**
     * Gets the ClassMetadata instance of the document class this persister is used for.
     *
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Executes all queued document insertions and returns any generated post-insert
     * identifiers that were created as a result of the insertions.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @param array $options Array of options to be used with batchInsert()
     * @return array An array of any generated post-insert IDs. This will be an empty array
     *               if the document class does not use the IDENTITY generation strategy.
     */
    public function executeInserts(array $options = array())
    {
        if ( ! $this->queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $inserts = array();
        foreach ($this->queuedInserts as $oid => $document) {
            $data = $this->dp->prepareInsertData($document);
            if ( ! $data) {
                continue;
            }
            $inserts[$oid] = $data;
        }
        if (empty($inserts)) {
            return;
        }
        $this->collection->batchInsert($inserts, $options);

        foreach ($inserts as $oid => $data) {
            $document = $this->queuedInserts[$oid];
            $postInsertIds[] = array($data['_id'], $document);
            if ($this->class->isFile()) {
                $this->dm->getHydrator()->hydrate($document, $data);
            }
        }
        $this->queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * Updates the already persisted document if it has any new changesets.
     *
     * @param object $document
     * @param array $options Array of options to be used with update()
     */
    public function update($document, array $options = array())
    {
        $id = $this->uow->getDocumentIdentifier($document);
        $update = $this->dp->prepareUpdateData($document);

        if ( ! empty($update)) {
            if ($this->dm->getEventManager()->hasListeners(ODMEvents::onUpdatePrepared)) {
                $this->dm->getEventManager()->dispatchEvent(
                    ODMEvents::onUpdatePrepared, new OnUpdatePreparedArgs($this->dm, $document, $update)
                );
            }
            $id = $this->class->getDatabaseIdentifierValue($id);
            $this->collection->update(array('_id' => $id), $update, $options);
        }
    }

    /**
     * Removes document from mongo
     *
     * @param mixed $document
     * @param array $options Array of options to be used with remove()
     */
    public function delete($document, array $options = array())
    {
        $id = $this->uow->getDocumentIdentifier($document);

        $this->collection->remove(array(
            '_id' => $this->class->getDatabaseIdentifierValue($id)
        ), $options);
    }

    /**
     * Refreshes a managed document.
     *
     * @param object $document The document to refresh.
     */
    public function refresh($document)
    {
        $id = $this->uow->getDocumentIdentifier($document);
        if ($this->dm->loadByID($this->class->name, $id) === null) {
            throw new \InvalidArgumentException(sprintf('Could not loadByID because ' . $this->class->name . ' '.$id . ' does not exist anymore.'));
        }
    }

    /**
     * Loads an document by a list of field criteria.
     *
     * @param array $query The criteria by which to load the document.
     * @param object $document The document to load the data into. If not specified,
     *        a new document is created.
     * @param $assoc The association that connects the document to load to another document, if any.
     * @param array $hints Hints for document creation.
     * @return object The loaded and managed document instance or NULL if the document can not be found.
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     * @todo Modify DocumentManager to use this method instead of its own hard coded
     */
    public function load(array $query = array(), array $select = array())
    {
        $result = $this->collection->findOne($query, $select);
        if ($result !== null) {
            return $this->uow->getOrCreateDocument($this->documentName, $result);
        }
        return null;
    }

    /**
     * Lood document by its identifier.
     *
     * @param string $id
     * @return object|null
     */
    public function loadById($id)
    {
        $result = $this->collection->findOne(array(
            '_id' => $this->class->getDatabaseIdentifierValue($id)
        ));
        if ($result !== null) {
            return $this->uow->getOrCreateDocument($this->documentName, $result);
        }
        return null;
    }

    /**
     * Loads a list of documents by a list of field criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $query = array(), array $select = array())
    {
        $cursor = $this->collection->find($query, $select);
        return new MongoCursor($this->dm, $this->dm->getUnitOfWork(), $this->dm->getHydrator(), $this->class, $this->dm->getConfiguration(), $cursor);
    }

    /**
     * Checks whether the given managed document exists in the database.
     *
     * @param object $document
     * @return boolean TRUE if the document exists in the database, FALSE otherwise.
     */
    public function exists($document)
    {
        $id = $this->class->getIdentifierObject($document);
        return (bool) $this->collection->findOne(array(array('_id' => $id)), array('_id'));
    }
}
