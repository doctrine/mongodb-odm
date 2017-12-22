<?php

namespace Doctrine\ODM\MongoDB;

/**
 * LockException
 *
 * @since 1.0
 */
class LockException extends MongoDBException
{
    private $document;

    public function __construct($msg, $document = null)
    {
        parent::__construct($msg);
        $this->document = $document;
    }

    /**
     * Gets the document that caused the exception.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    public static function lockFailed($document)
    {
        return new self('A lock failed on a document.', $document);
    }

    public static function lockFailedVersionMissmatch($document, $expectedLockVersion, $actualLockVersion)
    {
        return new self('The optimistic lock failed, version ' . $expectedLockVersion . ' was expected, but is actually '.$actualLockVersion, $document);
    }

    public static function notVersioned($documentName)
    {
        return new self('Document ' . $documentName . ' is not versioned.');
    }

    public static function invalidLockFieldType($type)
    {
        return new self('Invalid lock field type '.$type.'. Lock field must be int.');
    }

    public static function invalidVersionFieldType($type)
    {
        return new self('Invalid version field type '.$type.'. Version field must be int or date.');
    }
}
