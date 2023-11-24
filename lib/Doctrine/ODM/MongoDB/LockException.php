<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

final class LockException extends MongoDBException
{
    public function __construct(string $msg, private ?object $document = null)
    {
        parent::__construct($msg);
    }

    /**
     * Gets the document that caused the exception.
     */
    public function getDocument(): ?object
    {
        return $this->document;
    }

    public static function lockFailed(?object $document): self
    {
        return new self('A lock failed on a document.', $document);
    }

    public static function lockFailedVersionMissmatch(object $document, int $expectedLockVersion, int $actualLockVersion): self
    {
        return new self('The optimistic lock failed, version ' . $expectedLockVersion . ' was expected, but is actually ' . $actualLockVersion, $document);
    }

    public static function notVersioned(string $documentName): self
    {
        return new self('Document ' . $documentName . ' is not versioned.');
    }

    public static function invalidLockFieldType(string $type): self
    {
        return new self('Invalid lock field type ' . $type . '. Lock field must be int.');
    }

    public static function invalidVersionFieldType(string $type): self
    {
        return new self('Type ' . $type . ' does not implement Versionable interface.');
    }
}
