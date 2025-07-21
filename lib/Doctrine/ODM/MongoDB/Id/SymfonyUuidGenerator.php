<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Uid\UuidV7;

use function array_values;
use function implode;
use function in_array;
use function sprintf;

/** @internal */
final class SymfonyUuidGenerator extends AbstractIdGenerator
{
    private const SUPPORTED_TYPES = [
        1 => UuidV1::class,
        4 => UuidV4::class,
        7 => UuidV7::class,
    ];

    public function __construct(private readonly string $class)
    {
        if (! in_array($this->class, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid UUID type "%s". Expected one of: %s.', $this->class, implode(', ', array_values(self::SUPPORTED_TYPES))));
        }
    }

    public function generate(DocumentManager $dm, object $document): Uuid
    {
        return new $this->class();
    }
}
