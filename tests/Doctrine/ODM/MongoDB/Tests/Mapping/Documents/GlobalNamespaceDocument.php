<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class DoctrineGlobal_Article
{
    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $headline;

    /**
     * @ODM\Field(type="string")
     */
    protected $text;

    /**
     * @ODM\ReferenceMany(targetDocument="DoctrineGlobal_User")
     */
    protected $author;

    /**
     * @ODM\ReferenceMany(targetDocument="\DoctrineGlobal_User")
     */
    protected $editor;
}

/**
 * @ODM\Document
 */
class DoctrineGlobal_User
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     * @var string
     */
    private $username;

    /**
     * @ODM\Field(type="string")
     * @var string
     */
    private $email;
}
