<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File(chunkSizeBytes: 12345)]
class File
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\File\Filename]
    private $filename;

    /** @var int|null */
    #[ODM\File\ChunkSize]
    private $chunkSize;

    /** @var int|null */
    #[ODM\File\Length]
    private $length;

    /** @var DateTime|null */
    #[ODM\File\UploadDate]
    private $uploadDate;

    /** @var FileMetadata|null */
    #[ODM\File\Metadata(targetDocument: FileMetadata::class)]
    private $metadata;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getChunkSize(): ?int
    {
        return $this->chunkSize;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getUploadDate(): ?DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function getMetadata(): ?FileMetadata
    {
        return $this->metadata;
    }

    public function getOrCreateMetadata(): FileMetadata
    {
        if (! $this->metadata) {
            $this->metadata = new FileMetadata();
        }

        return $this->metadata;
    }
}
