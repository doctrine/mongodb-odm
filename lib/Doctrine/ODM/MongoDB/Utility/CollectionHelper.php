<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

use function in_array;

/**
 * Utility class used to unify checks on how collection strategies should behave.
 *
 * @internal
 */
final class CollectionHelper
{
    public const DEFAULT_STRATEGY = ClassMetadata::STORAGE_STRATEGY_PUSH_ALL;

    /**
     * Returns whether update query must be included in query updating owning document.
     */
    public static function isAtomic(string $strategy): bool
    {
        return $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET || $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY;
    }

    /**
     * Returns whether Collection hold associative array.
     */
    public static function isHash(string $strategy): bool
    {
        return $strategy === ClassMetadata::STORAGE_STRATEGY_SET || $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET;
    }

    /**
     * Returns whether Collection hold array indexed by consecutive numbers.
     */
    public static function isList(?string $strategy): bool
    {
        return $strategy !== ClassMetadata::STORAGE_STRATEGY_SET && $strategy !== ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET;
    }

    /**
     * Returns whether strategy uses $set to update its data.
     */
    public static function usesSet(string $strategy): bool
    {
        return in_array(
            $strategy,
            [
                ClassMetadata::STORAGE_STRATEGY_SET,
                ClassMetadata::STORAGE_STRATEGY_SET_ARRAY,
                ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
                ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
            ],
        );
    }
}
