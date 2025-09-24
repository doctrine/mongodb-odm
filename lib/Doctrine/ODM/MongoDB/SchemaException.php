<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use RuntimeException;

use function implode;
use function sprintf;

/**
 * Exception for schema-related issues.
 */
final class SchemaException extends RuntimeException
{
    /**
     * @internal
     *
     * @param string[] $missingIndexes
     */
    public static function missingSearchIndex(string $documentClass, array $missingIndexes): self
    {
        return new self(sprintf('The document class "%s" is missing the following search index(es): "%s"', $documentClass, implode('", "', $missingIndexes)));
    }
}
