<?php

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Interface for PersistentCollection classes generator.
 *
 * @since 1.1
 */
interface PersistentCollectionGenerator
{
    /**
     * Loads persistent collection class.
     *
     * @param string $collectionClass FQCN of base collection class
     * @param int $autoGenerate
     * @return string FQCN of generated class
     */
    public function loadClass($collectionClass, $autoGenerate);

    /**
     * Generates persistent collection class.
     *
     * @param string $class
     * @param string $dir
     * @return void
     */
    public function generateClass($class, $dir);
}
