<?php

declare(strict_types=1);

namespace TestDocuments;

use Doctrine\Common\Collections\Collection;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\Profile;

class NullableFieldsDocument
{
    /** @var string|null */
    protected $id;

    /** @var string|null */
    protected $username;

    /** @var Address|null */
    protected $address;

    /** @var Profile|null */
    protected $profile;

    /** @var Collection<int, Phonenumber> */
    protected $phonenumbers;

    /** @var Collection<int, Group> */
    protected $groups;
}
