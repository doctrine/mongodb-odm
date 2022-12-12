<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

final class UploadOptions
{
    /** @var mixed */
    public $_id;

    /** @var bool|null */
    public $disableMD5;

    /** @var object|null */
    public $metadata;

    /** @var int|null */
    public $chunkSizeBytes;
}
