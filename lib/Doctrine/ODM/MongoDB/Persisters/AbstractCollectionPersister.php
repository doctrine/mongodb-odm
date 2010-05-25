<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
abstract class AbstractCollectionPersister
{
    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $_dm;

    /**
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    protected $_uow;

    protected $_documentPersister;

    public function __construct(DocumentManager $dm, $documentPersister)
    {
        $this->_dm = $dm;
        $this->_uow = $dm->getUnitOfWork();
        $this->_documentPersister = $documentPersister;
    }

    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @param PersistentCollection $coll
     */
    public function delete(PersistentCollection $coll)
    {
        $class = $coll->getTypeClass();
        $collection = $this->_dm->getDocumentCollection($class->name);
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
            $this->_documentPersister->delete($doc);
        }
    }

    public function insertDocs(PersistentCollection $coll)
    {
        foreach ($coll as $doc) {
            $this->_documentPersister->addInsert($doc);
        }
    }
}
