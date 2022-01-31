<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

use function rand;

/**
 * @template TKey of array-key
 * @template TElement
 * @template-extends ArrayCollection<TKey, TElement>
 */
class CollWithNullableReturnType extends ArrayCollection
{
    public function maybeGetDate(): ?DateTime
    {
        return rand(0, 1) ? new DateTime() : null;
    }
}
