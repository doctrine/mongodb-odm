<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @template TKey of array-key
 * @template TElement
 * @template-extends ArrayCollection<TKey, TElement>
 */
class CollWithPHP81Types extends ArrayCollection
{
    public function intersection(Collection&ArrayCollection $param): Collection&ArrayCollection
    {
        return $param;
    }

    public function never(): never
    {
        die('You shall not pass');
    }
}
