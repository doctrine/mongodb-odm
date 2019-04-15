<?php

namespace Doctrine\ODM\MongoDB\Iterator;

interface Iterator extends \Iterator
{
    /**
     * @return array
     */
    public function toArray();
}
