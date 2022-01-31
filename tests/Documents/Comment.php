<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(repositoryClass=CommentRepository::class) */
class Comment
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $text;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, cascade={"all"})
     *
     * @var User|null
     */
    public $author;

    /**
     * @ODM\ReferenceOne(targetDocument=BlogPost::class, inversedBy="comments", cascade={"all"})
     *
     * @var BlogPost|null
     */
    public $parent;

    /**
     * @ODM\Field(type="date")
     * @ODM\Index(order="1")
     *
     * @var DateTime
     */
    public $date;

    /**
     * @ODM\Field(type="bool")
     *
     * @var bool
     */
    public $isByAdmin = false;

    public function __construct(string $text, DateTime $date, bool $isByAdmin = false)
    {
        $this->text      = $text;
        $this->date      = $date;
        $this->isByAdmin = $isByAdmin;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
