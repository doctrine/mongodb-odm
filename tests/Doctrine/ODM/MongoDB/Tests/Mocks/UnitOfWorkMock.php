<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\UnitOfWork;
use function spl_object_hash;

class UnitOfWorkMock extends UnitOfWork
{
    private $_mockDataChangeSets = [];
    private $_persisterMock;

    public function getDocumentPersister($documentName)
    {
        return $this->_persisterMock[$documentName] ?? parent::getDocumentPersister($documentName);
    }

    /**
     * @param <type> $document
     * @override
     */
    public function getDocumentChangeSet($document)
    {
        $oid = spl_object_hash($document);

        return $this->_mockDataChangeSets[$oid] ?? parent::getDocumentChangeSet($document);
    }

    /* MOCK API */

    /**
     * Sets a (mock) persister for a document class that will be returned when
     * getDocumentPersister() is invoked for that class.
     *
     * @param <type> $documentName
     * @param <type> $persister
     */
    public function setDocumentPersister($documentName, $persister)
    {
        $this->_persisterMock[$documentName] = $persister;
    }

    public function setDataChangeSet($document, array $mockChangeSet)
    {
        $this->_mockDataChangeSets[spl_object_hash($document)] = $mockChangeSet;
    }

    public function setDocumentState($document, $state)
    {
        $this->_documentStates[spl_object_hash($document)] = $state;
    }

    public function setOriginalDocumentData($document, array $originalData)
    {
        $this->_originalDocumentData[spl_object_hash($document)] = $originalData;
    }
}
