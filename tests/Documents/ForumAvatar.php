<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class ForumAvatar
{
    #[ODM\Id]
    public ?string $id;

    #[ODM\Field]
    public ?string $url = null;
}
