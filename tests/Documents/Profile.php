<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="profiles") */
class Profile
{
    /** @Id */
    public $profileId;

    /** @Field */
    public $firstName;

    /** @Field */
    public $lastName;

    /** @ReferenceOne(targetDocument="File") */
    public $image;
}