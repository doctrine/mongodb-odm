<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
#[ODM\Document]
class DoctrineGlobal_Article
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    protected $headline;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    protected $text;

    /**
     * @ODM\ReferenceMany(targetDocument=DoctrineGlobal_User::class)
     *
     * @var DoctrineGlobal_User|null
     */
    #[ODM\ReferenceMany(targetDocument: DoctrineGlobal_User::class)]
    protected $author;

    /**
     * @ODM\ReferenceMany(targetDocument=DoctrineGlobal_User::class)
     *
     * @var Collection<int, DoctrineGlobal_User>
     */
    #[ODM\ReferenceMany(targetDocument: DoctrineGlobal_User::class)]
    protected $editor;
}

/** @ODM\Document */
#[ODM\Document]
class DoctrineGlobal_User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private $email;
}
