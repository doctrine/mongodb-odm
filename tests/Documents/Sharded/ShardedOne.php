<?php

declare(strict_types=1);

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/**
 * @ODM\Document(collection="sharded.one")
 * @ODM\Indexes(
 *     @ODM\Index(keys={"k"="asc"})
 * )
 * @ODM\ShardKey(keys={"k"="asc"})
 */
class ShardedOne
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title = 'test';

    /**
     * @ODM\Field(name="k", type="string")
     *
     * @var string
     */
    public $key = 'testing';

    /**
     * @ODM\ReferenceOne(targetDocument=ShardedUser::class)
     *
     * @var ShardedUser|null
     */
    public $user;
}
