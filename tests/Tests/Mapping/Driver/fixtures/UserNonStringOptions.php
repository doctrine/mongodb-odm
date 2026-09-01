<?php

declare(strict_types=1);

namespace TestDocuments;

use Doctrine\Common\Collections\Collection;
use Documents\Group;
use Documents\Profile;

class UserNonStringOptions
{
    protected ?string $id;

    protected ?Profile $profile;

    /** @var Collection<int, Group> */
    protected Collection $groups;
}
