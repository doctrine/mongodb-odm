<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

/**
 * @template TKey
 * @template TValue
 * @template-extends \Iterator<TKey, TValue>
 */
interface Iterator extends \Iterator
{
    /**
     * @psalm-return array<TKey, TValue>
     */
    public function toArray(): array;
}
