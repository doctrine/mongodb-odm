<?php

declare(strict_types=1);

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'sharded.users')]
#[ODM\ShardKey(keys: ['user' => 'asc'])]
class ShardedByUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var ShardedUser|null */
    #[ODM\ReferenceOne(targetDocument: 'Documents\Sharded\ShardedUser', name: 'db_user')]
    public $user;
}
