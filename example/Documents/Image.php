<?php

namespace Documents;

/** @Document */
class Image
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    /** @File */
    private $file;

    /** @Field */
    private $uploadDate;

    /** @Field */
    private $length;

    /** @Field */
    private $chunkSize;

    /** @Field */
    private $md5;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }
}