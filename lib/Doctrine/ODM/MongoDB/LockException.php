<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * LockException
 *
 * @final
 */
class LockException extends MongoDBException
{
    /** @var object|null */
    private $document;

    public function __construct(string $msg, ?object $document = null)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($msg);
        $this->document = $document;
    }

    /**
     * Gets the document that caused the exception.
     */
    public function getDocument() : ?object
    {
        return $this->document;
    }

    public static function lockFailed(?object $document) : self
    {
        return new self('A lock failed on a document.', $document);
    }

    public static function lockFailedVersionMissmatch(object $document, int $expectedLockVersion, int $actualLockVersion) : self
    {
        return new self('The optimistic lock failed, version ' . $expectedLockVersion . ' was expected, but is actually ' . $actualLockVersion, $document);
    }

    public static function notVersioned(string $documentName) : self
    {
        return new self('Document ' . $documentName . ' is not versioned.');
    }

    public static function invalidLockFieldType(string $type) : self
    {
        return new self('Invalid lock field type ' . $type . '. Lock field must be int.');
    }

    public static function invalidVersionFieldType(string $type) : self
    {
        return new self('Invalid version field type ' . $type . '. Version field must be int or date.');
    }
}
