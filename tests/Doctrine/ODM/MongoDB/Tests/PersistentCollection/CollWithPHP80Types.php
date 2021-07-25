<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CollWithPHP80Types extends ArrayCollection
{
    public function mixed(mixed $param) : mixed
    {
        return $param;
    }

    public function union(Collection|ArrayCollection $param) : Collection|ArrayCollection
    {
        return $param;
    }

    public function static() : static
    {
        return $this;
    }

    public function nullableStatic() : ?static
    {
        return $this;
    }
}
