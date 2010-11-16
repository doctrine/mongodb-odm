<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

/**
 * DocumentPersister implementation used for mocking during tests.
 */
class DocumentPersisterMock extends \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
{
    private $inserts = array();
    private $updates = array();
    private $deletes = array();
    private $identityColumnValueCounter = 0;
    private $mockIdGeneratorType;
    private $postInsertIds = array();
    private $existsCalled = false;

    /**
     * @param <type> $document
     * @return <type>
     * @override
     */
    public function insert($document, array $options = array())
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = array($id, $document);
        return $id;
    }

    public function addInsert($document)
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = array($id, $document);
        return $id;
    }

    public function executeInserts(array $options = array())
    {
        return $this->postInsertIds;
    }

    public function update($document, array $options = array())
    {
        $this->updates[] = $document;
    }

    public function exists($document)
    {
        $this->existsCalled = true;
    }
    
    public function delete($document, array $options = array())
    {
        $this->deletes[] = $document;
    }
    
    public function getInserts()
    {
        return $this->inserts;
    }
    
    public function getUpdates()
    {
        return $this->updates;
    }
    
    public function getDeletes()
    {
        return $this->deletes;
    }
    
    public function reset()
    {
        $this->existsCalled = false;
        $this->identityColumnValueCounter = 0;
        $this->inserts = array();
        $this->updates = array();
        $this->deletes = array();
    }

    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}