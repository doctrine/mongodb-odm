<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

interface Iterator extends \Iterator
{
    public function toArray(): array;
}
