<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="profiles") */
class Profile
{
    /** @Id */
    private $profileId;

    /** @Field */
    private $firstName;

    /** @Field */
    private $lastName;

    /** @ReferenceOne(targetDocument="File", cascade={"all"}) */
    private $image;

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setImage(File $image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }
}