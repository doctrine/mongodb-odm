<?php

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 * @ODM\ShardKey(fields={"id"="hashed"})
 */
class ShardedUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;
}