<?php

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;

class CollWithNullableReturnType extends ArrayCollection
{
    public function maybeGetDate(): ?\DateTime
    {
        return rand(0, 1) ? new \DateTime() : null;
    }
}
