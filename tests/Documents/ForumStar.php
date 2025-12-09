<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Special entity that only has the $id field.
 */
#[ODM\Document]
class ForumStar
{
    #[ODM\Id]
    public ?string $id;
}
