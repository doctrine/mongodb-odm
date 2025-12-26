<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\File]
class FileWithoutMetadata
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\File\Filename]
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
