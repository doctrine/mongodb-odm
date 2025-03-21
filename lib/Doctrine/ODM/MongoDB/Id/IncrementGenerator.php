<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * IncrementGenerator is responsible for generating auto increment identifiers. It uses
 * a collection and generates the next id by using $inc on a field named "current_id".
 *
 * The 'collection' property determines which collection name is used to store the
 * id values. If not specified it defaults to 'doctrine_increment_ids'.
 *
 * The 'key' property determines the document ID used to store the id values in the
 * collection. If not specified it defaults to the name of the collection for the
 * document.
 */
class IncrementGenerator extends AbstractIdGenerator
{
    /** @var string|null */
    protected $collection = null;

    /** @var string|null */
    protected $key = null;

    /** @var int */
    protected $startingId = 1;

    /**
     * @param string $collection
     *
     * @return void
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function setStartingId(int $startingId): void
    {
        $this->startingId = $startingId;
    }

    public function generate(DocumentManager $dm, object $document)
    {
        $className = $document::class;
        $db        = $dm->getDocumentDatabase($className);

        $key            = $this->key ?: $dm->getDocumentCollection($className)->getCollectionName();
        $collectionName = $this->collection ?: 'doctrine_increment_ids';
        $collection     = $db->getCollection($collectionName);

        /*
         * Unable to use '$inc' and '$setOnInsert' together due to known bug.
         * @see https://jira.mongodb.org/browse/SERVER-10711
         * Results in error: Cannot update 'current_id' and 'current_id' at the same time
         */
        $query   = ['_id' => $key, 'current_id' => ['$exists' => true]];
        $update  = ['$inc' => ['current_id' => 1]];
        $options = ['upsert' => false, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER];
        $result  = $collection->findOneAndUpdate($query, $update, $options);

        /*
         * Updated nothing - counter doesn't exist, creating new counter.
         * Not bothering with {$exists: false} in the criteria as that won't avoid
         * an exception during a possible race condition.
         */
        if ($result === null) {
            $query   = ['_id' => $key];
            $update  = ['$inc' => ['current_id' => $this->startingId]];
            $options = ['upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER];
            $collection->findOneAndUpdate($query, $update, $options);

            return $this->startingId;
        }

        return $result['current_id'] ?? $this->startingId;
    }
}
