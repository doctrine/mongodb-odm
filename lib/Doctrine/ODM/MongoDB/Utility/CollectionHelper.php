<?php

namespace Doctrine\ODM\MongoDB\Utility;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Utility class used to unify checks on how collection strategies should behave.
 *
 * @since   1.0
 * @internal
 */
class CollectionHelper
{
    const DEFAULT_STRATEGY = ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL;

    /**
     * Returns whether update query must be included in query updating owning document.
     * 
     * @param string $strategy
     * @return bool
     */
    public static function isAtomic($strategy)
    {
        return $strategy === ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET || $strategy === ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET_ARRAY;
    }

    /**
     * Returns whether Collection hold associative array.
     * 
     * @param string $strategy
     * @return bool
     */
    public static function isHash($strategy)
    {
        return $strategy === ClassMetadataInfo::STORAGE_STRATEGY_SET || $strategy === ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET;
    }
    
    /**
     * Returns whether Collection hold array indexed by consecutive numbers.
     * 
     * @param string $strategy
     * @return bool
     */
    public static function isList($strategy)
    {
        return $strategy !== ClassMetadataInfo::STORAGE_STRATEGY_SET && $strategy !== ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET;
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
                ClassMetadataInfo::STORAGE_STRATEGY_SET,
                ClassMetadataInfo::STORAGE_STRATEGY_SET_ARRAY,
                ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET,
                ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET_ARRAY
            ]
        );
    }
}
