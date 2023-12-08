<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class ForumAvatar
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}
