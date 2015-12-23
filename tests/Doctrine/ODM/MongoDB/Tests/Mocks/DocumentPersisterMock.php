<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

/**
 * DocumentPersister implementation used for mocking during tests.
 */
class DocumentPersisterMock extends \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
{
    private $inserts = [];
    private $upserts = [];
    private $updates = [];
    private $deletes = [];
    private $identityColumnValueCounter = 1;
    private $mockIdGeneratorType;
    private $postInsertIds = [];
    private $existsCalled = false;

    public function insert($document, array $options = [])
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
        return $id;
    }

    public function addInsert($document)
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
        return $id;
    }

    public function addUpsert($document)
    {
        $this->upserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
        return $id;
    }

    public function executeInserts(array $options = [])
    {
    }

    public function executeUpserts(array $options = [])
    {
    }

    public function update($document, array $options = [])
    {
        $this->updates[] = $document;
    }

    public function exists($document)
    {
        $this->existsCalled = true;
    }

    public function delete($document, array $options = [])
    {
        $this->deletes[] = $document;
    }

    public function getInserts()
    {
        return $this->inserts;
    }

    public function getUpserts()
    {
        return $this->upserts;
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
        $this->identityColumnValueCounter = 1;
        $this->inserts = [];
        $this->updates = [];
        $this->deletes = [];
    }

    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}
