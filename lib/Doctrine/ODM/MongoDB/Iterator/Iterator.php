<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

/**
 * @template TValue
 * @template-extends \Iterator<mixed, TValue>
 */
interface Iterator extends \Iterator
{
    /**
     * @psalm-return array<mixed, TValue>
     */
    public function toArray(): array;
}
