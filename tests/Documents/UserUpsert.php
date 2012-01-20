<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users_upsert")
 */
class UserUpsert
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\Int */
    public $hits;

    /** @ODM\Increment */
    public $count;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}) */
    public $groups;
}