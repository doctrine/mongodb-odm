<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Generator;

use function array_filter;
use function assert;
use function iterator_to_array;

final class EncryptionFieldMap
{
    public function __construct(private ClassMetadataFactoryInterface $classMetadataFactory)
    {
    }

    /**
     * Generate the encryption field map from the class metadata.
     *
     * @param class-string $className
     */
    public function getEncryptionFieldMap(string $className): array
    {
        $classMetadata = $this->classMetadataFactory->getMetadataFor($className);

        return iterator_to_array($this->createEncryptionFieldMap($classMetadata));
    }

    private function createEncryptionFieldMap(ClassMetadata $classMetadata, string $path = ''): Generator
    {
        if ($classMetadata->isEncrypted && ! $classMetadata->isEmbeddedDocument) {
            throw MappingException::rootDocumentCannotBeEncrypted($classMetadata->getName());
        }

        foreach ($classMetadata->fieldMappings as $mapping) {
            // @todo support polymorphic types and inheritence?
            // Add fields recursively
            if ($mapping['embedded'] ?? false) {
                $embedMetadata = $this->classMetadataFactory->getMetadataFor($mapping['targetDocument']);

                // When the embedded document class is encrypted, the field is encrypted,
                // but none of the embedded fields are encrypted separately.
                if ($embedMetadata->isEncrypted) {
                    $mapping['encrypt'] ??= []; // @todo get the keyId
                } elseif (! isset($mapping['encrypt'])) {
                    yield from $this->createEncryptionFieldMap(
                        $embedMetadata,
                        $path . $mapping['name'] . '.',
                    );
                }
            }

            if (! isset($mapping['encrypt'])) {
                continue;
            }

            $field = [
                'path' => $path . $mapping['name'],
                'bsonType' => match ($mapping['type']) {
                    'one' => 'object',
                    'many' => 'array',
                    default => $mapping['type'],
                },
                // @todo allow setting a keyId in #[Encrypt] attribute
                'keyId' => null, // Generate the key automatically
            ];

            // When queryType is null, the field is not queryable
            if (isset($mapping['encrypt']['queryType'])) {
                $field['queries'] = array_filter($mapping['encrypt'], static fn ($v) => $v !== null);
                assert($field['queries']['queryType'] instanceof EncryptQuery);
                $field['queries']['queryType'] = $field['queries']['queryType']->value;
            }

            yield $field;
        }
    }
}
