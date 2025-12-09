<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(repositoryClass: CommentRepository::class)]
class Comment
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $text;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['all'])]
    public $author;

    /** @var BlogPost|null */
    #[ODM\ReferenceOne(targetDocument: BlogPost::class, inversedBy: 'comments', cascade: ['all'])]
    public $parent;

    /** @var DateTime */
    #[ODM\Field(type: 'date')]
    #[ODM\Index(order: '1')]
    public $date;

    /** @var bool */
    #[ODM\Field(type: 'bool')]
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
