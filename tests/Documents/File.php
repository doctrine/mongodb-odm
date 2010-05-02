<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="files") */
class File
{
    /** @Id */
    public $id;

    /** @Field */
    public $name;

    /** @File */
    public $file;

    /** @Field */
    public $uploadDate;

    /** @Field */
    public $length;

    /** @Field */
    public $chunkSize;

    /** @Field */
    public $md5;
}