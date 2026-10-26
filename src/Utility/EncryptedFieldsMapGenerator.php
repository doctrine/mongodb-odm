<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Types\Type;
use LogicException;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
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

            $classMap = $this->createEncryptedFieldsForClass($classMetadata);
            if ($classMap === []) {
                continue;
            }

            $encryptedFieldsMap[$classMetadata->getName()] = ['fields' => array_values($classMap)];
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

        $fields = $this->createEncryptedFieldsForClass($classMetadata);

        if ($fields === []) {
            return null;
        }

        return ['fields' => array_values($fields)];
    }

    /**
     * @param array<class-string, true> $visitedClasses
     * @phpstan-param ClassMetadata<T> $classMetadata
     *
     * @return array<string, array{path: string, bsonType: string, keyId: null}> Keyed by field path
     *
     * @template T of object
     */
    private function createEncryptedFieldsForClass(
        ClassMetadata $classMetadata,
        string $parentPath = '',
        array $visitedClasses = [],
    ): array {
        if ($classMetadata->isEncrypted && ! $classMetadata->isEmbeddedDocument) {
            throw MappingException::rootDocumentCannotBeEncrypted($classMetadata->getName());
        }

        if (isset($visitedClasses[$classMetadata->getName()])) {
            // Prevent infinite recursion due to circular references in the metadata
            return [];
        }

        $visitedClasses[$classMetadata->getName()] = true;

        $fields = [];

        foreach ($classMetadata->fieldMappings as $mapping) {
            // Add fields recursively
            if (($mapping['embedded'] ?? false) && ! isset($mapping['encrypt'])) {
                $embeddedMetadata = $this->getEmbeddedClassMetadata($mapping);

                if ($embeddedMetadata === []) {
                    throw MappingException::embeddedDocumentClassCannotBeDetermined(
                        $classMetadata->getName(),
                        $mapping['fieldName'],
                    );
                }

                $encryptedClasses = count(array_filter(
                    $embeddedMetadata,
                    static fn (ClassMetadata $metadata): bool => $metadata->isEncrypted,
                ));

                if ($encryptedClasses === count($embeddedMetadata)) {
                    // When the embedded document class is encrypted, the field is encrypted
                    // as a whole object, and none of the embedded fields are encrypted separately.
                    $mapping['encrypt'] = [];
                } elseif ($encryptedClasses > 0) {
                    throw MappingException::partiallyEncryptedDiscriminatorMap(
                        $classMetadata->getName(),
                        $mapping['fieldName'],
                    );
                }

                if (! isset($mapping['encrypt'])) {
                    // The fields of the embedded document are merged individually. The paths
                    // are used as keys, so a field declared by several classes of a
                    // discriminator map is listed once.
                    foreach ($embeddedMetadata as $embedMetadata) {
                        $fields += $this->createEncryptedFieldsForClass(
                            $embedMetadata,
                            $parentPath . $mapping['name'] . '.',
                            $visitedClasses,
                        );
                    }
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

            $fields[$field['path']] = $field;
        }

        return $fields;
    }

    /**
     * Returns the metadata of the classes that an embedded field can hold.
     *
     * An embedded document is either mapped to a single class with "targetDocument",
     * or to the classes listed in "discriminatorMap" when the field is polymorphic.
     * When neither is set, the class is only known at runtime from the stored value,
     * so it cannot be determined when building the encrypted fields map.
     *
     * @param array<string, mixed> $mapping
     *
     * @return list<ClassMetadata<object>>
     */
    private function getEmbeddedClassMetadata(array $mapping): array
    {
        if (isset($mapping['targetDocument'])) {
            return [$this->classMetadataFactory->getMetadataFor($mapping['targetDocument'])];
        }

        if (isset($mapping['discriminatorMap'])) {
            $metadata = [];
            foreach (array_unique($mapping['discriminatorMap']) as $embeddedClass) {
                $metadata[] = $this->classMetadataFactory->getMetadataFor($embeddedClass);
            }

            return $metadata;
        }

        return [];
    }
}
