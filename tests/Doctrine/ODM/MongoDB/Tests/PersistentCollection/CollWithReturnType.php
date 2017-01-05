<?php

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;

class CollWithReturnType extends ArrayCollection
{
    public function getDate(): \DateTime
    {
        return new \DateTime();
    }
}
