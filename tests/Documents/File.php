<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="files") */
class File
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    /** @File */
    private $file;

    /** @Integer @NotSaved */
    private $length;

    /** @Integer @NotSaved */
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