<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * Contract for types that can compare two PHP values by value.
 */
interface EquatableType
{
    /**
     * Tells whether the two given PHP values of this type are equal.
     */
    public function valuesAreEqual(object $a, object $b): bool;
}
