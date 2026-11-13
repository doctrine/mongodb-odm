<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use IteratorAggregate;

/**
 * Marks a custom collection class for many associations that delegates to a
 * managed collection.
 *
 * A class implementing this interface must declare exactly one non-static,
 * non-readonly property typed as Doctrine\Common\Collections\Collection. The
 * ODM reads and writes the managed collection in that property through
 * reflection. The class does not have to implement the Collection interface.
 *
 * Implementing this interface opts the class into delegation. Classes that
 * only implement Collection keep using generated persistent collection
 * classes.
 *
 * @see https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/custom-collections.html
 *
 * @phpstan-template TKey of array-key
 * @phpstan-template T
 * @template-extends IteratorAggregate<TKey, T>
 */
interface DelegateCollection extends IteratorAggregate
{
}
