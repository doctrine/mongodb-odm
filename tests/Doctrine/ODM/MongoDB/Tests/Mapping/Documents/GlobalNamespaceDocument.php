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
     * @ODM\String
     */
    protected $headline;

    /**
     * @ODM\String
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
     * @ODM\String
     * @var string
     */
    private $username;

    /**
     * @ODM\String
     * @var string
     */
    private $email;
}
