<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @Document(db="doctrine_odm_tests", collection="users")
 * @InheritanceType("COLLECTION_PER_CLASS")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"special"="Documents\SpecialUser"})
 */
class User
{
    /** @Id */
    public $id;

    /** @Field */
    public $username;

    /** @Field */
    public $password;

    /** @EmbedOne(targetDocument="Address") */
    public $address;

    /** @ReferenceOne(targetDocument="Profile") */
    public $profile;

    /** @EmbedMany(targetDocument="Phonenumber") */
    public $phonenumbers;

    /** @ReferenceMany(targetDocument="Group") */
    public $groups;

    /** @ReferenceOne(targetDocument="Account", cascadeDelete=true) */
    public $account;

    /** @Field(name=0) */
    public $aliasTest;

    public function __construct()
    {
        $this->phonenumbers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }
}