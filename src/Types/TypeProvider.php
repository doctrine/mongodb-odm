<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Traversable;

/**
 * Provides the {@see Type} instances available to a DocumentManager, keyed by type name.
 *
 * Iterating over a provider yields every type it knows about. Implementations are free to resolve
 * types lazily, so iteration may instantiate them.
 *
 * @extends Traversable<string, Type>
 */
interface TypeProvider extends Traversable
{
    /**
     * Finds a type by the given name.
     *
     * @throws InvalidTypeException
     */
    public function get(string $name): Type;

    /**
     * Checks if there is a type of the given name.
     */
    public function has(string $name): bool;
}
