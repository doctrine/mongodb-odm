<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

final class UploadOptions
{
    /** @var mixed */
    public $id;

    /** @var bool|null */
    public $disableMD5;

    /** @var int|null */
    public $chunkSizeBytes;

    /** @var object|null */
    public $metadata;
}
