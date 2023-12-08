<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;

#[Index(keys: ['slug' => 'asc'], options: ['unique' => 'true'])]
#[ODM\MappedSuperclass]
abstract class CmsPage
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $slug;
}
