<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(repositoryClass=CommentRepository::class) */
class Comment
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $text;

    /** @ODM\ReferenceOne(targetDocument=User::class, cascade={"all"}) */
    public $author;

    /** @ODM\ReferenceOne(targetDocument=BlogPost::class, inversedBy="comments", cascade={"all"}) */
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
