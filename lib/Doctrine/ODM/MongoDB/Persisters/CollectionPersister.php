<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Persisters\DataPreparer,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

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
        $this->updateRows($coll);
        $this->insertRows($coll);
    }

    private function deleteRows(PersistentCollection $coll)
    {
        $pull = array();

        $mapping = $coll->getMapping();
        $deleteDiff = $coll->getDeleteDiff();
        $pull = $this->preparePushAndPullData($mapping, $deleteDiff);
        if ($pull) {
            $path = $this->getDocumentFieldPath($mapping);
            $query = array($this->cmd . 'pullAll' => array($path => $pull));
            $this->executeQuery($id, $coll->getOwner(), $mapping, $query);
        }
    }

    private function updateRows(PersistentCollection $coll)
    {
    }

    private function insertRows(PersistentCollection $coll)
    {
        $push = array();

        $mapping = $coll->getMapping();
        $insertDiff = $coll->getInsertDiff();
        $push = $this->preparePushAndPullData($mapping, $insertDiff);
        if ($push) {
            $path = $this->getDocumentFieldPath($mapping);
            $query = array($this->cmd.'pushAll' => array($path => $push));
            list($parent, $path) = $this->uow->getParentAssociations($)
            $this->executeQuery(, $mapping, $query);
        }
    }

    private function getDocumentId($document, ClassMetadata $class)
    {
        return $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
    }

    private function getDocumentFieldPath($document)
    {
        $fieldNames = array();
        $parent = $document;
        while (null !== ($association = $this->getParentAssociation($parent))) {
            list($mapping, $parent) = $association;
            $fieldNames[] = $mapping['name'];
        }
        return implode('.', array_reverse($fieldNames));
    }

    private function preparePushAndPullData(array $mapping, array $documents)
    {
        $data = array();
        foreach ($documents as $document) {
            if (isset($mapping['reference'])) {
                $data[] = $this->dp->prepareReferencedDocValue($mapping, $document);
            } else {
                $data[] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
            }
        }
        return $data;
    }

    private function executeQuery($parentDocument, array $mapping, array $query)
    {
        $className = get_class($parentDocument);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($parentDocument));
        $collection = $this->dm->getDocumentCollection($className);
        $collection->update(array('_id' => $id), $query, array('safe' => true));
    }
}