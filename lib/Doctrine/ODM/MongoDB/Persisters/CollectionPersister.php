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
        $query = array($this->cmd . 'unset' => array($propertyPath => true));
        $this->executeQuery($parent, $mapping, $query, $options);
    }

    public function update(PersistentCollection $coll, array $options)
    {
        $this->deleteRows($coll, $options);
        $this->insertRows($coll, $options);
    }

    private function deleteRows(PersistentCollection $coll, array $options)
    {
        $mapping = $coll->getMapping();
        $owner = $coll->getOwner();
        list($propertyPath, $parent, $parentMapping) = $this->getPathAndParent($owner, $mapping);
        $deleteDiff = $coll->getDeleteDiff();
        if ($deleteDiff) {
            $query = array($this->cmd.'unset' => array());
            foreach ($deleteDiff as $key => $document) {
                $query[$this->cmd.'unset'][$propertyPath.'.'.$key] = true;
            }
            $this->executeQuery($parent, $mapping, $query, $options);

            /**
             * @todo This is a hack right now because we don't have a proper way to remove
             * an element from an array by its key. Unsetting the key results in the element
             * being left in the array as null so we have to pull null values.
             *
             * "Using "$unset" with an expression like this "array.$" will result in the array item becoming null, not being removed. You can issue an update with "{$pull:{x:null}}" to remove all nulls."
             * http://www.mongodb.org/display/DOCS/Updating#Updating-%24unset
             */
            $this->executeQuery($parent, $mapping, array($this->cmd.'pull' => array($propertyPath => null)), $options);
        }
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
                if (isset($mapping['reference'])) {
                    $query[$this->cmd.'pushAll'][$propertyPath][] = $this->dp->prepareReferencedDocValue($mapping, $document);
                } else {
                    $query[$this->cmd.'pushAll'][$propertyPath][] = $this->dp->prepareEmbeddedDocValue($mapping, $document);
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
            list($m, $parent, $path) = $association;
            $fields[] = $path;
        }
        $propertyPath = implode('.', array_reverse($fields));
        $path = $mapping['name'];
        if ($propertyPath) {
            $path = $propertyPath.'.'.$path;
        }
        return array($path, $parent, $mapping);
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