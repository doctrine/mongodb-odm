<?php

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="sharded.users")
 * @ODM\ShardKey(keys={"user"="desc"})
 */
class ShardedByUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="Documents\Sharded\ShardedUser", name="db_user") */
    public $user;
}
