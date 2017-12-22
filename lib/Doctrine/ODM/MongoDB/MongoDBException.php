<?php

namespace Doctrine\ODM\MongoDB;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 *
 * @since       1.0
 */
class MongoDBException extends \Exception
{
    /**
     * @param string $documentName
     * @param string $fieldName
     * @param string $method
     * @return MongoDBException
     */
    public static function invalidFindByCall($documentName, $fieldName, $method)
    {
        return new self(sprintf('Invalid find by call %s::$fieldName (%s)', $documentName, $fieldName, $method));
    }

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
     * @param string $methodName
     * @return MongoDBException
     */
    public static function findByRequiresParameter($methodName)
    {
        return new self("You need to pass a parameter to '".$methodName."'");
    }

    /**
     * @param string $documentNamespaceAlias
     * @return MongoDBException
     */
    public static function unknownDocumentNamespace($documentNamespaceAlias)
    {
        return new self("Unknown Document namespace alias '$documentNamespaceAlias'.");
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
        return new self("Invalid repository class '".$className."'. It must be a Doctrine\Common\Persistence\ObjectRepository.");
    }

    /**
     * @param string $type
     * @param string|array $expected
     * @param mixed $got
     * @return MongoDBException
     */
    public static function invalidValueForType($type, $expected, $got)
    {
        if (is_array($expected)) {
            $expected = sprintf('%s or %s',
                join(', ', array_slice($expected, 0, -1)),
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
        return new self(sprintf('Failed to enable sharding for database "%s". Error from MongoDB: %s',
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
        return new self(sprintf('Failed to ensure sharding for document "%s". Error from MongoDB: %s',
            $className,
            $errorMessage
        ));
    }
}
