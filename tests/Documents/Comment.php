<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use DateTime;

/** @ODM\Document(repositoryClass="Documents\CommentRepository") */
class Comment
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $text;

    /** @ODM\ReferenceOne(targetDocument="BlogPost", inversedBy="comments", cascade={"all"}) */
    public $parent;

    /** @ODM\Date @ODM\Index(order="1") */
    public $date;

    /** @ODM\Boolean */
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