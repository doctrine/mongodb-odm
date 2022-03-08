<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @psalm-import-type FieldMapping from ClassMetadata
 * @psalm-import-type FieldMappingConfig from ClassMetadata
 */
class ClassMetadataTestUtil
{
    /**
     * @psalm-param FieldMappingConfig $mapping
     * @psalm-return FieldMapping
     */
    public static function getFieldMapping(array $mapping): array
    {
        $defaultFieldMapping = [
            'type' => Type::STRING,
            'fieldName' => 'name',
            'name' => 'name',
            'isCascadeRemove' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeMerge' => false,
            'isCascadeDetach' => false,
            'isOwningSide' => false,
            'isInverseSide' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ];

        return $mapping + $defaultFieldMapping;
    }
}
