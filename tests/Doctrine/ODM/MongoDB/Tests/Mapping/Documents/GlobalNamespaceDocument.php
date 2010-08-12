<?php

/**
 * @Document
 */
class DoctrineGlobal_Article
{
    /**
     * @Id
     */
    protected $id;

    /**
     * @String
     */
    protected $headline;

    /**
     * @String
     */
    protected $text;

    /**
     * @ReferenceMany(targetDocument="DoctrineGlobal_User")
     */
    protected $author;

    /**
     * @ReferenceMany(targetDocument="\DoctrineGlobal_User")
     */
    protected $editor;
}

/**
 * @Document
 */
class DoctrineGlobal_User
{
    /**
     * @Id
     */
    private $id;

    /**
     * @String
     * @var string
     */
    private $username;

    /**
     * @String
     * @var string
     */
    private $email;
}