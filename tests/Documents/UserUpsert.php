<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users_upsert")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="discriminator")
 * @ODM\DiscriminatorMap({
 *     "user"="Documents\UserUpsert",
 *     "child"="Documents\UserUpsertChild"
 * })
 */
class UserUpsert
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="int") */
    public $hits;

    /** @ODM\Field(type="increment") */
    public $count;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}) */
    public $groups;
}
