<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Types\Type;
use Generator;
use LogicException;

use function array_filter;
use function iterator_to_array;
use function sprintf;

final class EncryptedFieldsMapGenerator
{
    public function __construct(private ClassMetadataFactoryInterface $classMetadataFactory)
    {
    }

    /**
     * Returns the full encryption fields map for a document manager
     *
     * @return array<class-string, array{fields: array<int, array{path: string, bsonType: string, keyId: null}>}>
     */
    public function getEncryptedFieldsMap(): array
    {
        $encryptedFieldsMap = [];

        $allMetadata = $this->classMetadataFactory->getAllMetadata();
        foreach ($allMetadata as $classMetadata) {
            if (! $classMetadata->isDocument()) {
                continue;
            }

            $classMap = iterator_to_array($this->createEncryptedFieldsForClass($classMetadata));
            if ($classMap === []) {
                continue;
            }

            $encryptedFieldsMap[$classMetadata->getName()] = ['fields' => $classMap];
        }

        return $encryptedFieldsMap;
    }

    /**
     * Generate the encryption field map from the class metadata.
     *
     * @param class-string $className
     *
     * @return array{fields: array<int, array{path: string, bsonType: string, keyId: null}>}|null
     */
    public function getEncryptedFieldsForClass(string $className): ?array
    {
        $classMetadata = $this->classMetadataFactory->getMetadataFor($className);

        if (! $classMetadata->isDocument()) {
            throw MongoDBException::notADocumentClass($className);
        }

        $fields = iterator_to_array($this->createEncryptedFieldsForClass($classMetadata));

        if ($fields === []) {
            return null;
        }

        return ['fields' => $fields];
    }

    /**
     * @param array<class-string, true> $visitedClasses
     * @phpstan-param ClassMetadata<T> $classMetadata
     *
     * @return Generator<int, array{path: string, bsonType: string, keyId: null}>
     *
     * @template T of object
     */
    private function createEncryptedFieldsForClass(
        ClassMetadata $classMetadata,
        string $parentPath = '',
        array $visitedClasses = [],
    ): Generator {
        if ($classMetadata->isEncrypted && ! $classMetadata->isEmbeddedDocument) {
            throw MappingException::rootDocumentCannotBeEncrypted($classMetadata->getName());
        }

        if (isset($visitedClasses[$classMetadata->getName()])) {
            // Prevent infinite recursion due to circular references in the metadata
            return;
        }

        foreach ($classMetadata->fieldMappings as $mapping) {
            // Add fields recursively
            if ($mapping['embedded'] ?? false) {
                $embedMetadata = $this->classMetadataFactory->getMetadataFor($mapping['targetDocument']);

                // When the embedded document class is encrypted, the field is encrypted,
                // but none of the embedded fields are encrypted separately.
                if ($embedMetadata->isEncrypted) {
                    $mapping['encrypt'] ??= [];
                } elseif (! isset($mapping['encrypt'])) {
                    yield from $this->createEncryptedFieldsForClass(
                        $embedMetadata,
                        $parentPath . $mapping['name'] . '.',
                        $visitedClasses + [$classMetadata->getName() => true],
                    );
                }
            }

            if (! isset($mapping['encrypt'])) {
                continue;
            }

            $field = [
                'path' => $parentPath . $mapping['name'],
                'bsonType' => match ($mapping['type']) {
                    ClassMetadata::ONE, Type::HASH => 'object',
                    ClassMetadata::MANY, Type::COLLECTION => 'array',
                    Type::INT, Type::INTEGER => 'int',
                    Type::INT64 => 'long',
                    Type::FLOAT => 'double',
                    Type::DECIMAL128 => 'decimal',
                    Type::DATE, Type::DATE_IMMUTABLE => 'date',
                    Type::TIMESTAMP => 'timestamp',
                    Type::OBJECTID => 'objectId',
                    Type::STRING => 'string',
                    Type::BINDATA, Type::BINDATABYTEARRAY, Type::BINDATAFUNC, Type::BINDATACUSTOM, Type::BINDATAUUID, Type::BINDATAMD5, Type::BINDATAUUIDRFC4122 => 'binData',
                    Type::BOOL, Type::BOOLEAN => 'bool',
                    default => throw new LogicException(sprintf('Type "%s" is not supported in encrypted fields map.', $mapping['type'])),
                },
                'keyId' => null, // Generate the key automatically
            ];

            // When queryType is null, the field is not queryable
            if (isset($mapping['encrypt']['queryType'])) {
                $field['queries']              = array_filter($mapping['encrypt'], static fn ($v) => $v !== null);
                $field['queries']['queryType'] = $field['queries']['queryType']->value;
            }

            yield $field;
        }
    }
}
