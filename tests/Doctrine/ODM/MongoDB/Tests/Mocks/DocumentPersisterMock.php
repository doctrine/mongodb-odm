<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;

/**
 * DocumentPersister implementation used for mocking during tests.
 */
class DocumentPersisterMock extends DocumentPersister
{
    private $inserts = [];
    private $upserts = [];
    private $updates = [];
    private $deletes = [];
    private $identityColumnValueCounter = 1;
    private $postInsertIds = [];
    private $existsCalled = false;

    public function insert($document, array $options = [])
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
        return $id;
    }

    public function addInsert(object $document): void
    {
        $this->inserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
    }

    public function addUpsert(object $document): void
    {
        $this->upserts[] = $document;
        $id = $this->identityColumnValueCounter++;
        $this->postInsertIds[$id] = [$id, $document];
    }

    public function executeInserts(array $options = []): void
    {
    }

    public function executeUpserts(array $options = []): void
    {
    }

    public function update(object $document, array $options = []): void
    {
        $this->updates[] = $document;
    }

    public function exists($document): bool
    {
        $this->existsCalled = true;

        return false;
    }

    public function delete(object $document, array $options = []): void
    {
        $this->deletes[] = $document;
    }

    public function getInserts(): array
    {
        return $this->inserts;
    }

    public function getUpserts(): array
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
