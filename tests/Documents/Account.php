<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="accounts") */
class Account
{
    /** @Id */
    public $id;

    /** @Field */
    public $name;
}