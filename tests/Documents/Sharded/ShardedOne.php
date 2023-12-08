<?php

declare(strict_types=1);

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

#[ODM\Document(collection: 'sharded.one')]
#[ODM\ShardKey(keys: ['k' => 'asc'])]
#[ODM\Index(keys: ['k' => 'asc'])]
class ShardedOne
{
    /** @var ObjectId|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $title = 'test';

    /** @var string */
    #[ODM\Field(name: 'k', type: 'string')]
    public $key = 'testing';

    /** @var ShardedUser|null */
    #[ODM\ReferenceOne(targetDocument: ShardedUser::class)]
    public $user;
}
