<?php

namespace Documents;

use DateTime;

/** @Document(repositoryClass="Documents\CommentRepository") */
class Comment
{
    /** @Id */
    public $id;

    /** @String */
    public $text;

    /** @ReferenceOne(targetDocument="BlogPost", inversedBy="comments", cascade={"all"}) */
    public $parent;

    /** @Date @Index(order="1") */
    public $date;

    /** @Boolean */
    public $isByAdmin = false;

    public function __construct($text, DateTime $date, $isByAdmin = false)
    {
        $this->text = $text;
        $this->date = $date;
        $this->isByAdmin = $isByAdmin;
    }

    public function getText()
    {
        return $this->text;
    }

}