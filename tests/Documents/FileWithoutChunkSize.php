<?php

declare(strict_types=1);

namespace Documents;

use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\File */
class FileWithoutChunkSize
{
    /** @ODM\Id */
    private $id;

    /** @ODM\File\Filename */
    private $filename;

    /** @ODM\File\ChunkSize */
    private $chunkSize;

    /** @ODM\File\Length */
    private $length;

    /** @ODM\File\UploadDate */
    private $uploadDate;

    /** @ODM\File\Metadata(targetDocument=FileMetadata::class) */
    private $metadata;

    public function getId() : ?string
    {
        return $this->id;
    }

    public function getFilename() : ?string
    {
        return $this->filename;
    }

    public function getChunkSize() : ?int
    {
        return $this->chunkSize;
    }

    public function getLength() : ?int
    {
        return $this->length;
    }

    public function getUploadDate() : DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function getMetadata() : ?FileMetadata
    {
        return $this->metadata;
    }

    public function getOrCreateMetadata() : FileMetadata
    {
        if (! $this->metadata) {
            $this->metadata = new FileMetadata();
        }

        return $this->getMetadata();
    }
}
