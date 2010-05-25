<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicCollectionPersister
{
    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $_dm;

    /**
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    protected $_uow;

    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
        $this->_uow = $dm->getUnitOfWork();
    }

    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @param PersistentCollection $coll
     */
    public function delete(PersistentCollection $coll)
    {
        $this->deleteDocs($coll);
    }

    /**
     * Updates the given collection, synchronizing it's state with the database
     * by inserting, updating and deleting individual elements.
     *
     * @param PersistentCollection $coll
     */
    public function update(PersistentCollection $coll)
    {
        $this->deleteDocs($coll);
        $this->insertDocs($coll);
    }

    public function deleteDocs(PersistentCollection $coll)
    {
        foreach ($coll as $doc) {
            $persister = $this->_uow->getDocumentPersister(get_class($doc));
            $persister->delete($doc);
        }
    }

    public function insertDocs(PersistentCollection $coll)
    {
        $document = $coll->getOwner();
        $mapping = $coll->getMapping();
        $id = $this->_uow->getDocumentIdentifier($document);
        $collection = $this->_dm->getDocumentCollection($coll->getTypeClass()->name);
        foreach ($coll as $doc) {
            $persister = $this->_uow->getDocumentPersister(get_class($doc));
            $update[] = $persister->prepareUpdateData($doc);
        }
        $collection->update(
            array('_id' => $id),
            array('$' . $mapping['strategy'] => array(
                $mapping['fieldName'] => $update
            ))
        );
    }
}
