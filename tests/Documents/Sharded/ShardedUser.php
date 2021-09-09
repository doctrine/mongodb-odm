<?php

declare(strict_types=1);

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="sharded.users")
 * @ODM\ShardKey(keys={"_id"="hashed"})
 */
class ShardedUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}
