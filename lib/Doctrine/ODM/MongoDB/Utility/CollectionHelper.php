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
class CollectionHelper
{
    public const DEFAULT_STRATEGY = ClassMetadata::STORAGE_STRATEGY_PUSH_ALL;

    /**
     * Returns whether update query must be included in query updating owning document.
     *
     * @param string $strategy
     * @return bool
     */
    public static function isAtomic($strategy)
    {
        return $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET || $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY;
    }

    /**
     * Returns whether Collection hold associative array.
     *
     * @param string $strategy
     * @return bool
     */
    public static function isHash($strategy)
    {
        return $strategy === ClassMetadata::STORAGE_STRATEGY_SET || $strategy === ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET;
    }

    /**
     * Returns whether Collection hold array indexed by consecutive numbers.
     *
     * @param string $strategy
     * @return bool
     */
    public static function isList($strategy)
    {
        return $strategy !== ClassMetadata::STORAGE_STRATEGY_SET && $strategy !== ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET;
    }

    /**
     * Returns whether strategy uses $set to update its data.
     *
     * @param string $strategy
     * @return bool
     */
    public static function usesSet($strategy)
    {
        return in_array(
            $strategy,
            [
                ClassMetadata::STORAGE_STRATEGY_SET,
                ClassMetadata::STORAGE_STRATEGY_SET_ARRAY,
                ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
                ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
            ]
        );
    }
}
