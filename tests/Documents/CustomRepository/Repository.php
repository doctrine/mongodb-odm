<?php

declare(strict_types=1);

namespace Documents\CustomRepository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/** @template-extends DocumentRepository<Document> */
class Repository extends DocumentRepository
{
}
