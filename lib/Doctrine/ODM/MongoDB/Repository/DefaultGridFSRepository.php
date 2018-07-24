<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use const PATHINFO_BASENAME;
use function fclose;
use function fopen;
use function pathinfo;

class DefaultGridFSRepository extends DocumentRepository implements GridFSRepository
{
    /**
     * @see Bucket::downloadToStream
     */
    public function downloadToStream($id, $destination): void
    {
        try {
            $this->getDocumentBucket()->downloadToStream($this->class->getDatabaseIdentifierValue($id), $destination);
        } catch (FileNotFoundException $e) {
            throw DocumentNotFoundException::documentNotFound($this->getClassName(), $id);
        }
    }

    /**
     * @see Bucket::openUploadStream
     */
    public function openUploadStream(string $filename, $metadata = null, ?int $chunkSizeBytes = null)
    {
        $options = $this->prepareOptions($metadata, $chunkSizeBytes);

        return $this->getDocumentBucket()->openUploadStream($filename, $options);
    }

    /**
     * @see Bucket::uploadFromStream
     */
    public function uploadFromStream(string $filename, $source, $metadata = null, ?int $chunkSizeBytes = null)
    {
        $options = $this->prepareOptions($metadata, $chunkSizeBytes);

        $databaseIdentifier = $this->getDocumentBucket()->uploadFromStream($filename, $source, $options);
        $documentIdentifier = $this->class->getPHPIdentifierValue($databaseIdentifier);

        return $this->dm->getReference($this->getClassName(), $documentIdentifier);
    }

    public function uploadFromFile(string $source, ?string $filename = null, $metadata = null, ?int $chunkSizeBytes = null)
    {
        $resource = fopen($source, 'r');
        if ($resource === false) {
            throw MongoDBException::cannotReadGridFSSourceFile($source);
        }

        if ($filename === null) {
            $filename = pathinfo($source, PATHINFO_BASENAME);
        }

        try {
            return $this->uploadFromStream($filename, $resource, $metadata, $chunkSizeBytes);
        } finally {
            fclose($resource);
        }
    }

    private function getDocumentBucket(): Bucket
    {
        return $this->dm->getDocumentBucket($this->documentName);
    }

    /**
     * @param object|null $metadata
     */
    private function prepareOptions($metadata = null, ?int $chunkSizeBytes = null): array
    {
        $options = [
            'chunkSizeBytes' => $chunkSizeBytes ?: $this->class->getChunkSizeBytes(),
        ];

        if ($metadata) {
            $options += ['metadata' => (object) $this->uow->getPersistenceBuilder()->prepareInsertData($metadata)];
        }

        return $options;
    }
}
