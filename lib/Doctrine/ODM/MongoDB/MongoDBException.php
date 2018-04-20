<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Persistence\ObjectRepository;
use function array_slice;
use function end;
use function get_class;
use function implode;
use function is_array;
use function is_object;
use function sprintf;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 *
 */
class MongoDBException extends \Exception
{
    /**
     * @return MongoDBException
     */
    public static function detachedDocumentCannotBeRemoved()
    {
        return new self('Detached document cannot be removed');
    }

    /**
     * @param string $state
     * @return MongoDBException
     */
    public static function invalidDocumentState($state)
    {
        return new self(sprintf('Invalid document state "%s"', $state));
    }

    /**
     * @param string $className
     * @return MongoDBException
     */
    public static function documentNotMappedToCollection($className)
    {
        return new self(sprintf('The "%s" document is not mapped to a MongoDB database collection.', $className));
    }

    /**
     * @return MongoDBException
     */
    public static function documentManagerClosed()
    {
        return new self('The DocumentManager is closed.');
    }

    /**
     * @param string $documentNamespaceAlias
     * @return MongoDBException
     */
    public static function unknownDocumentNamespace($documentNamespaceAlias)
    {
        return new self(sprintf("Unknown Document namespace alias '%s'.", $documentNamespaceAlias));
    }

    /**
     * @param string $className
     * @return MongoDBException
     */
    public static function cannotPersistMappedSuperclass($className)
    {
        return new self('Cannot persist an embedded document, aggregation result document or mapped superclass ' . $className);
    }

    /**
     * @param string $className
     * @return MongoDBException
     */
    public static function invalidDocumentRepository($className)
    {
        return new self(sprintf("Invalid repository class '%s'. It must be a %s.", $className, ObjectRepository::class));
    }

    /**
     * @param string       $type
     * @param string|array $expected
     * @param mixed        $got
     * @return MongoDBException
     */
    public static function invalidValueForType($type, $expected, $got)
    {
        if (is_array($expected)) {
            $expected = sprintf(
                '%s or %s',
                implode(', ', array_slice($expected, 0, -1)),
                end($expected)
            );
        }
        if (is_object($got)) {
            $gotType = get_class($got);
        } elseif (is_array($got)) {
            $gotType = 'array';
        } else {
            $gotType = 'scalar';
        }
        return new self(sprintf('%s type requires value of type %s, %s given', $type, $expected, $gotType));
    }

    /**
     * @param string $field
     * @param string $className
     * @return MongoDBException
     */
    public static function shardKeyFieldCannotBeChanged($field, $className)
    {
        return new self(sprintf('Shard key field "%s" in class "%s" cannot be changed.', $field, $className));
    }

    /**
     * @param string $field
     * @param string $className
     * @return MongoDBException
     */
    public static function shardKeyFieldMissing($field, $className)
    {
        return new self(sprintf('Shard key field "%s" in class "%s" is missing.', $field, $className));
    }

    /**
     * @param string $dbName
     * @param string $errorMessage
     * @return MongoDBException
     */
    public static function failedToEnableSharding($dbName, $errorMessage)
    {
        return new self(sprintf(
            'Failed to enable sharding for database "%s". Error from MongoDB: %s',
            $dbName,
            $errorMessage
        ));
    }

    /**
     * @param string $className
     * @param string $errorMessage
     * @return MongoDBException
     */
    public static function failedToEnsureDocumentSharding($className, $errorMessage)
    {
        return new self(sprintf(
            'Failed to ensure sharding for document "%s". Error from MongoDB: %s',
            $className,
            $errorMessage
        ));
    }

    /**
     * @return MongoDBException
     */
    public static function commitInProgress()
    {
        return new self('There is already a commit operation in progress. Did you call flush from an event listener?');
    }

    public static function documentBucketOnlyAvailableForGridFSFiles(string $className): self
    {
        return new self(sprintf('Cannot fetch document bucket for document "%s".', $className));
    }
}
