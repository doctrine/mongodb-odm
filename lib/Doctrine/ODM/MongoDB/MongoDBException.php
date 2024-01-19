<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\Persistence\ObjectRepository;
use Exception;

use function array_slice;
use function end;
use function implode;
use function is_array;
use function is_object;
use function sprintf;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 */
class MongoDBException extends Exception
{
    public static function detachedDocumentCannotBeRemoved(): self
    {
        return new self('Detached document cannot be removed');
    }

    public static function invalidDocumentState(int $state): self
    {
        return new self(sprintf('Invalid document state "%s"', $state));
    }

    public static function documentNotMappedToCollection(string $className): self
    {
        return new self(sprintf('The "%s" document is not mapped to a MongoDB database collection.', $className));
    }

    public static function documentManagerClosed(): self
    {
        return new self('The DocumentManager is closed.');
    }

    public static function unknownDocumentNamespace(string $documentNamespaceAlias): self
    {
        return new self(sprintf("Unknown Document namespace alias '%s'.", $documentNamespaceAlias));
    }

    public static function cannotPersistMappedSuperclass(string $className): self
    {
        return new self(sprintf('Cannot persist object of class "%s" as it is not a persistable document.', $className));
    }

    public static function invalidDocumentRepository(string $className): self
    {
        return new self(sprintf("Invalid repository class '%s'. It must be a %s.", $className, ObjectRepository::class));
    }

    public static function invalidGridFSRepository(string $className): self
    {
        return new self(sprintf("Invalid repository class '%s'. It must be a %s.", $className, GridFSRepository::class));
    }

    public static function invalidClassMetadataFactory(string $className): self
    {
        return new self(sprintf("Invalid class metadata factory class '%s'. It must be a %s.", $className, ClassMetadataFactoryInterface::class));
    }

    /**
     * @param string|string[] $expected
     * @param mixed           $got
     *
     * @return MongoDBException
     */
    public static function invalidValueForType(string $type, $expected, $got): self
    {
        if (is_array($expected)) {
            $expected = sprintf(
                '%s or %s',
                implode(', ', array_slice($expected, 0, -1)),
                end($expected),
            );
        }

        if (is_object($got)) {
            $gotType = $got::class;
        } elseif (is_array($got)) {
            $gotType = 'array';
        } else {
            $gotType = 'scalar';
        }

        return new self(sprintf('%s type requires value of type %s, %s given', $type, $expected, $gotType));
    }

    public static function shardKeyFieldCannotBeChanged(string $field, string $className): self
    {
        return new self(sprintf('Shard key field "%s" in class "%s" cannot be changed.', $field, $className));
    }

    public static function shardKeyFieldMissing(string $field, string $className): self
    {
        return new self(sprintf('Shard key field "%s" in class "%s" is missing.', $field, $className));
    }

    public static function failedToEnableSharding(string $dbName, string $errorMessage): self
    {
        return new self(sprintf(
            'Failed to enable sharding for database "%s". Error from MongoDB: %s',
            $dbName,
            $errorMessage,
        ));
    }

    public static function failedToEnsureDocumentSharding(string $className, string $errorMessage): self
    {
        return new self(sprintf(
            'Failed to ensure sharding for document "%s". Error from MongoDB: %s',
            $className,
            $errorMessage,
        ));
    }

    public static function commitInProgress(): self
    {
        return new self('There is already a commit operation in progress. Did you call flush from an event listener?');
    }

    public static function documentBucketOnlyAvailableForGridFSFiles(string $className): self
    {
        return new self(sprintf('Cannot fetch document bucket for document "%s".', $className));
    }

    public static function cannotPersistGridFSFile(string $className): self
    {
        return new self(sprintf('Cannot persist GridFS file for class "%s" through UnitOfWork.', $className));
    }

    public static function cannotReadGridFSSourceFile(string $filename): self
    {
        return new self(sprintf('Cannot open file "%s" for uploading to GridFS.', $filename));
    }

    public static function invalidTypeMap(string $part, string $epectedType): self
    {
        return new self(sprintf('Invalid typemap provided. Type "%s" is required for "%s".', $epectedType, $part));
    }

    public static function cannotRefreshDocument(): self
    {
        return new self('Failed to fetch current data of document being refreshed. Was it removed in the meantime?');
    }

    public static function cannotCreateRepository(string $className): self
    {
        return new self(sprintf('Cannot create repository for class "%s".', $className));
    }

    public static function transactionalSessionMismatch(): self
    {
        return new self('The transactional operation cannot be executed because it was started in a different session.');
    }
}
