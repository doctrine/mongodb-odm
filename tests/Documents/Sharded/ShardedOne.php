<?php

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="sharded.one")
 * @ODM\ShardKey(keys={"k"="asc"})
 */
class ShardedOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $title = 'test';

    /** @ODM\String(name="k") */
    public $key = 'testing';
}