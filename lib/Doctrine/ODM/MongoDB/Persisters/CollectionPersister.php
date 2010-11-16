<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Persisters\DataPreparer,
    Doctrine\ODM\MongoDB\UnitOfWork;

class CollectionPersister
{
    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The DataPreparer instance.
     *
     * @var Doctrine\ODM\MongoDB\Persisters\DataPreparer
     */
    private $dp;

    private $cmd;

    /**
     * Contructs a new CollectionPersister instance.
     *
     * @param DocumentManager $dm
     * @param DataPreparer $dp
     * @param UnitOfWork $uow
     * @param string $cmd
     */
    public function __construct(DocumentManager $dm, DataPreparer $dp, UnitOfWork $uow, $cmd)
    {
        $this->dm = $dm;
        $this->dp = $dp;
        $this->uow = $uow;
        $this->cmd = $cmd;
    }

    public function delete(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        $className = get_class($owner);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($owner));
        $collection = $this->dm->getDocumentCollection($className);
        $query = array($this->cmd . 'unset' => array($mapping['name'] => true));
        $collection->update(array('_id' => $id), $query, array('safe' => true));
    }

    public function update(PersistentCollection $coll)
    {
        $this->deleteRows($coll);
        $this->insertRows($coll);
    }

    private function deleteRows(PersistentCollection $coll)
    {
        $pull = array();
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        $className = get_class($owner);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($owner));
        $collection = $this->dm->getDocumentCollection($className);
        $deleteDiff = $coll->getDeleteDiff();
        foreach ($deleteDiff as $delete) {
            if (isset($mapping['reference'])) {
                $pull[] = $this->dp->prepareReferencedDocValue($mapping, $delete);
            } else {
                $pull[] = $this->dp->prepareEmbeddedDocValue($mapping, $delete);
            }
        }
        if ($pull) {
            $query = array($this->cmd . 'pullAll' => array($mapping['name'] => $pull));
            $collection->update(array('_id' => $id), $query, array('safe' => true));
        }
    }

    private function insertRows(PersistentCollection $coll)
    {
        $push = array();
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        $className = get_class($owner);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($owner));
        $collection = $this->dm->getDocumentCollection($className);
        $insertDiff = $coll->getInsertDiff();
        foreach ($insertDiff as $insert) {
            if (isset($mapping['reference'])) {
                $push[] = $this->dp->prepareReferencedDocValue($mapping, $insert);
            } else {
                $push[] = $this->dp->prepareEmbeddedDocValue($mapping, $insert);
            }
        }
        if ($push) {
            $query = array($this->cmd.'pushAll' => array($mapping['name'] => $push));
            $collection->update(array('_id' => $id), $query, array('safe' => true));
        }
    }
}