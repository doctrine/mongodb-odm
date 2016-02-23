<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
