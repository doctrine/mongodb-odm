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
        $pull = array();
        foreach ($deleteDiff as $document) {
            if (isset($mapping['reference'])) {
                $pull[] = $this->dp->prepareReferencedDocValue($mapping, $document);
            } else {
                $pull[] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
            }
        }
        if ($pull) {

            list($propertyPath, $parentDocument) = $this->getPathAndParent($document);

            $query = array($this->cmd . 'pullAll' => array($propertyPath => $pull));
            $this->executeQuery($parentDocument, $coll->getOwner(), $mapping, $query);
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
        $push = array();
        foreach ($insertDiff as $document) {
            if (isset($mapping['reference'])) {
                $push[] = $this->dp->prepareReferencedDocValue($mapping, $document);
            } else {
                $push[] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
            }
        }
        if ($push) {

            list($propertyPath, $parentDocument) = $this->getPathAndParent($document);

            $query = array($this->cmd.'pushAll' => array($propertyPath => $push));
            $this->executeQuery($parentDocument, $mapping, $query);
        }
    }

    private function getDocumentId($document, ClassMetadata $class)
    {
        return $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
    }

    private function getPathAndParent($document)
    {
        $fields = array();
        $parent = $document;
        while (null !== ($association = $this->uow->getParentAssociation($parent))) {
            list($mapping, $parent) = $association;
            $fields[] = $mapping['name'];
        }
        return array(implode('.', array_reverse($fields)), $parent);
    }

    private function executeQuery($parentDocument, array $mapping, array $query)
    {
        $className = get_class($parentDocument);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($parentDocument));
        $collection = $this->dm->getDocumentCollection($className);
        var_dump($query);
        $collection->update(array('_id' => $id), $query, array('safe' => true));
    }
}