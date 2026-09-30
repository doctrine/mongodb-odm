<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Stringable;

use function get_debug_type;
use function is_scalar;
use function sprintf;
use function var_export;

/**
 * Thrown when a document is added to the identity map with an identifier that is
 * already mapped to a different document instance.
 */
final class DocumentIdentityCollisionException extends MongoDBException
{
    public static function create(object $existingDocument, object $newDocument, mixed $identifier): self
    {
        return new self(sprintf(
            'While adding a document of class %s with identifier %s to the identity map, '
            . 'another document of class %s was already present for the same identifier. '
            . 'Identifiers should uniquely map to document object instances. '
            . 'This problem may occur when application-provided identifiers are reused.',
            $newDocument::class,
            self::formatIdentifier($identifier),
            $existingDocument::class,
        ));
    }

    private static function formatIdentifier(mixed $identifier): string
    {
        return match (true) {
            is_scalar($identifier) => var_export($identifier, true),
            $identifier instanceof Stringable => (string) $identifier,
            default => get_debug_type($identifier),
        };
    }
}
