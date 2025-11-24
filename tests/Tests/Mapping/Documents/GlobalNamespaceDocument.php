<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DoctrineGlobal_Article
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $headline;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $text;

    /** @var DoctrineGlobal_User|null */
    #[ODM\ReferenceMany(targetDocument: DoctrineGlobal_User::class)]
    protected $author;

    /** @var Collection<int, DoctrineGlobal_User> */
    #[ODM\ReferenceMany(targetDocument: DoctrineGlobal_User::class)]
    protected $editor;
}


#[ODM\Document]
class DoctrineGlobal_User
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    private $username;

    /** @var string */
    #[ODM\Field(type: 'string')]
    private $email;
}
