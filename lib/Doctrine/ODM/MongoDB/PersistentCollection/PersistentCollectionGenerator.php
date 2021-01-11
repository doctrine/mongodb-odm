<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Interface for PersistentCollection classes generator.
 */
interface PersistentCollectionGenerator
{
    /**
     * Loads persistent collection class.
     *
     * @param string $collectionClass FQCN of base collection class
     *
     * @return string FQCN of generated class
     */
    public function loadClass(string $collectionClass, int $autoGenerate): string;

    /**
     * Generates persistent collection class.
     */
    public function generateClass(string $class, string $dir): void;
}
