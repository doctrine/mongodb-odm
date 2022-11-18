<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/** @template-extends DocumentRepository<User> */
class UserRepository extends DocumentRepository
{
}
