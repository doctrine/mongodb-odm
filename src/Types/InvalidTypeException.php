<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use InvalidArgumentException;

use function sprintf;

final class InvalidTypeException extends InvalidArgumentException
{
    public static function invalidTypeName(string $name): self
    {
        return new self(sprintf('Invalid type specified: "%s"', $name));
    }
}
