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
class CollWithPHP80Types extends ArrayCollection
{
    public function mixed(mixed $param): mixed
    {
        return $param;
    }

    /**
     * @param Collection<TKey, TElement>|ArrayCollection<TKey, TElement> $param
     *
     * @return Collection<TKey, TElement>|ArrayCollection<TKey, TElement>
     */
    public function union(Collection|ArrayCollection $param): Collection|ArrayCollection
    {
        return $param;
    }

    public function static(): static
    {
        return $this;
    }

    public function nullableStatic(): ?static
    {
        return $this;
    }
}
