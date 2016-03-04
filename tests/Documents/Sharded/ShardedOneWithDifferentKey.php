<?php

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Note: this document intentially collides with ShardedOne to test shard key changes
 *
 * @ODM\Document(collection="sharded.one")
 * @ODM\ShardKey(keys={"v"="asc"})
 */
class ShardedOneWithDifferentKey
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $title = 'test';

    /** @ODM\String(name="k") */
    public $key = 'testing';

    /** @ODM\String(name="v") */
    public $value = 'testing';
}
