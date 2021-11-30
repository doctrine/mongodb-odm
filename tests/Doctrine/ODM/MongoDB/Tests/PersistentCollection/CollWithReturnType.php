<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @template TKey of array-key
 * @template TElement
 * @template-extends ArrayCollection<TKey, TElement>
 */
class CollWithReturnType extends ArrayCollection
{
    public function getDate(): DateTime
    {
        return new DateTime();
    }
}
