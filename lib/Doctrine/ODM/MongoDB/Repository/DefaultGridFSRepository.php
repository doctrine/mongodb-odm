<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;

use function fclose;
use function fopen;
use function is_object;
use function pathinfo;

use const PATHINFO_BASENAME;

/**
 * @template T of object
 * @template-extends DocumentRepository<T>
 * @template-implements GridFSRepository<T>
 */
class DefaultGridFSRepository extends DocumentRepository implements GridFSRepository
{
    /** @see Bucket::openDownloadStream() */
    public function openDownloadStream($id)
    {
        try {
            return $this->getDocumentBucket()->openDownloadStream($this->class->getDatabaseIdentifierValue($id));
        } catch (FileNotFoundException) {
            throw DocumentNotFoundException::documentNotFound($this->getClassName(), $id);
        }
    }

    /** @see Bucket::downloadToStream */
    public function downloadToStream($id, $destination): void
    {
        try {
            $this->getDocumentBucket()->downloadToStream($this->class->getDatabaseIdentifierValue($id), $destination);
        } catch (FileNotFoundException) {
            throw DocumentNotFoundException::documentNotFound($this->getClassName(), $id);
        }
    }

    /** @see Bucket::openUploadStream */
    public function openUploadStream(string $filename, ?UploadOptions $uploadOptions = null)
    {
        $options = $this->prepareOptions($uploadOptions);

        return $this->getDocumentBucket()->openUploadStream($filename, $options);
    }

    /** @see Bucket::uploadFromStream */
    public function uploadFromStream(string $filename, $source, ?UploadOptions $uploadOptions = null)
    {
        $options = $this->prepareOptions($uploadOptions);

        $databaseIdentifier = $this->getDocumentBucket()->uploadFromStream($filename, $source, $options);
        $documentIdentifier = $this->class->getPHPIdentifierValue($databaseIdentifier);

        return $this->dm->getReference($this->getClassName(), $documentIdentifier);
    }

    public function uploadFromFile(string $source, ?string $filename = null, ?UploadOptions $uploadOptions = null)
    {
        $resource = fopen($source, 'r');
        if ($resource === false) {
            throw MongoDBException::cannotReadGridFSSourceFile($source);
        }

        if ($filename === null) {
            $filename = pathinfo($source, PATHINFO_BASENAME);
        }

        try {
            return $this->uploadFromStream($filename, $resource, $uploadOptions);
        } finally {
            fclose($resource);
        }
    }

    private function getDocumentBucket(): Bucket
    {
        return $this->dm->getDocumentBucket($this->documentName);
    }

    /**
     * @return array{
     *     _id?: mixed,
     *     chunkSizeBytes?: int,
     *     metadata?: object
     * }
     */
    private function prepareOptions(?UploadOptions $uploadOptions = null): array
    {
        if ($uploadOptions === null) {
            $uploadOptions = new UploadOptions();
        }

        $chunkSizeBytes = $uploadOptions->chunkSizeBytes ?: $this->class->getChunkSizeBytes();
        $options        = [];

        if ($uploadOptions->id !== null) {
            $options['_id'] = $uploadOptions->id;
        }

        if ($chunkSizeBytes !== null) {
            $options['chunkSizeBytes'] = $chunkSizeBytes;
        }

        if (! is_object($uploadOptions->metadata)) {
            return $options;
        }

        $metadataMapping = $this->class->getFieldMappingByDbFieldName('metadata');
        $metadata        = $this->uow->getPersistenceBuilder()->prepareEmbeddedDocumentValue($metadataMapping, $uploadOptions->metadata, true);

        return $options + ['metadata' => (object) $metadata];
    }
}
