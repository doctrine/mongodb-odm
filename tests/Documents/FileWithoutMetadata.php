<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File]
class FileWithoutMetadata
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\File\Filename
     *
     * @var string|null
     */
    private $filename;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }
}
