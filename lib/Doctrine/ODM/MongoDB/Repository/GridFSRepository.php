<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 * @template-extends ObjectRepository<T>
 */
interface GridFSRepository extends ObjectRepository
{
    /**
     * Opens a readable stream for reading a GridFS file.
     *
     * @param mixed $id File ID
     *
     * @return resource
     */
    public function openDownloadStream($id);

    /**
     * Writes the contents of a GridFS file to a writable stream.
     *
     * @param mixed    $id          File ID
     * @param resource $destination Writable Stream
     */
    public function downloadToStream($id, $destination): void;

    /**
     * Opens a writable stream for writing a GridFS file.
     *
     * @return resource
     */
    public function openUploadStream(string $filename, ?UploadOptions $uploadOptions = null);

    /**
     * Writes the contents of a readable stream to a GridFS file.
     *
     * @param resource $source Readable stream
     *
     * @return object The newly created GridFS file
     */
    public function uploadFromStream(string $filename, $source, ?UploadOptions $uploadOptions = null);

    /**
     * Writes the contents of a file to a GridFS file.
     *
     * @param string|null $filename The filename to upload the file with. If no filename is provided, the name of the source file will be used.
     *
     * @return object The newly created GridFS file
     */
    public function uploadFromFile(string $source, ?string $filename = null, ?UploadOptions $uploadOptions = null);
}
