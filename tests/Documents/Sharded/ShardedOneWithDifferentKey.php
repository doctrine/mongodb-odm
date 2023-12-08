<?php

declare(strict_types=1);

namespace Documents\Sharded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Note: this document intentially collides with ShardedOne to test shard key changes
 */
#[ODM\Document(collection: 'sharded.one')]
#[ODM\ShardKey(keys: ['v' => 'asc'])]
#[ODM\Index(keys: ['v' => 'asc'])]
class ShardedOneWithDifferentKey
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $title = 'test';

    /** @var string */
    #[ODM\Field(name: 'k', type: 'string')]
    public $key = 'testing';

    /** @var string */
    #[ODM\Field(name: 'v', type: 'string')]
    public $value = 'testing';
}
