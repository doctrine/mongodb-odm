<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

/**
 * @template-covariant TValue
 * @template-extends \Iterator<mixed, TValue>
 */
interface Iterator extends \Iterator
{
    /** @return array<mixed, TValue> */
    public function toArray(): array;
}
