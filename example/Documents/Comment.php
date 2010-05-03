<?php

namespace Documents;

/**
 * @Document
 */
class Comment extends BaseDocument
{
    /** @Field */
    private $comment;

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }
}