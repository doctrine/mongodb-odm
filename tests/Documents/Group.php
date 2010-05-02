<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="groups") */
class Group
{
    /** @Id */
    public $id;

    /** @Name */
    public $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }
}