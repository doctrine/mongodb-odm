<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

final class UploadOptions
{
    /** @var object|null */
    public $metadata;

    /** @var int|null */
    public $chunkSizeBytes;
}
