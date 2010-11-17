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

    public function delete(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        list($propertyPath, $parent, $parentMapping) = $this->getPathAndParent($owner, $mapping);
        $propertyPath = $propertyPath ? $propertyPath : $mapping['name'];
        $query = array($this->cmd . 'unset' => array($propertyPath => true));
        $this->executeQuery($parent, $mapping, $query, $options);
    }

    public function update(PersistentCollection $coll, array $options)
    {
        $this->deleteRows($coll, $options);
        $this->updateRows($coll, $options);
        $this->insertRows($coll, $options);
    }

    private function deleteRows(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        list($propertyPath, $parent, $parentMapping) = $this->getPathAndParent($owner, $mapping);
        $insertDiff = $coll->getDeleteDiff();
        if ($insertDiff) {
            $query = array($this->cmd.'pullAll' => array());
            foreach ($insertDiff as $key => $document) {
                $path = $mapping['name'];
                if ($propertyPath) {
                    $path = $propertyPath.'.'.$path;
                }
                if (isset($mapping['reference'])) {
                    $query[$this->cmd.'pullAll'][$path][] = $this->dp->prepareReferencedDocValue($mapping, $document);
                } else {
                    $query[$this->cmd.'pullAll'][$path][] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
                }
            }
            $this->executeQuery($parent, $mapping, $query, $options);
        }
    }

    private function updateRows(PersistentCollection $coll, array $options)
    {
    }

    private function insertRows(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        list($propertyPath, $parent, $parentMapping) = $this->getPathAndParent($owner, $mapping);
        $insertDiff = $coll->getInsertDiff();
        if ($insertDiff) {
            $query = array($this->cmd.'pushAll' => array());
            foreach ($insertDiff as $key => $document) {
                $path = $mapping['name'];
                if ($propertyPath) {
                    $path = $propertyPath.'.'.$path;
                }
                if (isset($mapping['reference'])) {
                    $query[$this->cmd.'pushAll'][$path][] = $this->dp->prepareReferencedDocValue($mapping, $document);
                } else {
                    $query[$this->cmd.'pushAll'][$path][] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
                }
            }
            $this->executeQuery($parent, $mapping, $query, $options);
        }
    }

    private function getDocumentId($document, ClassMetadata $class)
    {
        return $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
    }

    private function getPathAndParent($document, array $mapping)
    {
        $fields = array();
        $parent = $document;
        while (null !== ($association = $this->uow->getParentAssociation($parent))) {
            list($mapping, $parent, $path) = $association;
            $fields[] = $path;
        }
        return array(implode('.', array_reverse($fields)), $parent, $mapping);
    }

    private function executeQuery($parentDocument, array $mapping, array $query, array $options)
    {
        $className = get_class($parentDocument);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($parentDocument));
        $collection = $this->dm->getDocumentCollection($className);
        $collection->update(array('_id' => $id), $query, $options);
    }
}