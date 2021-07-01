<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * FIXME: reflection chokes if this class doesn't have a doc comment
 *
 * @template-extends DocumentRepository<Comment>
 */
class CommentRepository extends DocumentRepository
{
    public function findOneComment()
    {
        return $this->getDocumentPersister()
            ->loadAll([], ['date' => 'desc'], 1)
            ->current();
    }

    public function findManyComments(): Iterator
    {
        return $this->getDocumentPersister()->loadAll();
    }
}
