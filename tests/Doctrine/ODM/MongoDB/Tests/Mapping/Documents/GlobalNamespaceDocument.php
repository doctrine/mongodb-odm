<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class DoctrineGlobal_Article
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $headline;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $text;

    /**
     * @ODM\ReferenceMany(targetDocument=DoctrineGlobal_User::class)
     *
     * @var DoctrineGlobal_User|null
     */
    protected $author;

    /**
     * @ODM\ReferenceMany(targetDocument=\DoctrineGlobal_User::class)
     *
     * @var Collection<int, DoctrineGlobal_User>
     */
    protected $editor;
}

/** @ODM\Document */
class DoctrineGlobal_User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    private $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    private $email;
}
