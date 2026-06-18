<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Closure;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use LogicException;
use ReturnTypeWillChange;
use Traversable;

use function array_combine;
use function array_diff_key;
use function array_map;
use function array_udiff_assoc;
use function array_values;
use function count;
use function get_class;
use function is_object;
use function sprintf;

/**
 * Compatibility trait for PersistentCollection between collections 2 and 3.
 * @template TKey of array-key
 * @template T of object
 */
if (defined(Criteria::class . '::ASC')) {
    // collections 2
    /** @internal */
    trait PersistentCollectionCompatibility
    {
        abstract private function doAdd(mixed $value, bool $arrayAccess): bool;

        /**
         * Adds an element at the end of the collection.
         *
         * @param mixed $element The element to add.
         * @phpstan-param T $element
         *
         * @return true The return value is kept for BC reasons, but will be void in doctrine/mongodb-odm 3.0.
         */
        public function add(mixed $value): bool
        {
            $this->doAdd($value, false);

            return true;
        }
    }
} else {
    // collections 3
    /** @internal */
    trait PersistentCollectionCompatibility
    {
        abstract private function doAdd(mixed $value, bool $arrayAccess): bool;

        /**
         * Adds an element at the end of the collection.
         *
         * @param mixed $element The element to add.
         * @phpstan-param T $element
         */
        public function add(mixed $value): void
        {
            $this->doAdd($value, false);
        }
    }
}
