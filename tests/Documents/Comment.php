<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use DateTime;

/** @ODM\Document(repositoryClass="Documents\CommentRepository") */
class Comment
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $text;

    /** @ODM\ReferenceOne(targetDocument="BlogPost", inversedBy="comments", cascade={"all"}) */
    public $parent;

    /** @ODM\Field(type="date") @ODM\Index(order="1") */
    public $date;

    /** @ODM\Field(type="bool") */
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
