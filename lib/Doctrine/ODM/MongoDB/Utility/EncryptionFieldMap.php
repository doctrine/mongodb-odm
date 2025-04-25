<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Generator;

use function array_filter;
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
        return iterator_to_array($this->createEncryptionFieldMap($className));
    }

    private function createEncryptionFieldMap(string $className, string $path = ''): Generator
    {
        $classMetadata = $this->classMetadataFactory->getMetadataFor($className);
        foreach ($classMetadata->fieldMappings as $mapping) {
            // @todo support polymorphic types and inheritence?
            // Add fields recursively
            if ($mapping['embedded'] ?? false) {
                yield from $this->createEncryptionFieldMap($mapping['targetDocument'], $path . $mapping['name'] . '.');
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
            }

            yield $field;
        }
    }
}
