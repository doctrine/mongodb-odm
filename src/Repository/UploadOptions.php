<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

final class UploadOptions
{
    public mixed $id            = null;
    public ?int $chunkSizeBytes = null;
    public ?object $metadata    = null;
}
