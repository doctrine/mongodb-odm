<?php

namespace Documents;

use Doctrine\ODM\MongoDB\DocumentRepository;

/** FIXME: reflection chokes if this class doesn't have a doc comment */
class CommentRepository extends DocumentRepository
{
    public function findOneComment()
    {
        return $this->findBy(array())
            ->sort(array('date' => 'desc'))
            ->limit(1)
            ->getSingleResult();
    }

    public function findManyComments()
    {
        return $this->findBy(array());
    }
}