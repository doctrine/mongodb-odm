<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\Indexes({
 *   @ODM\Index(keys={"country"="asc", "zip"="asc", "city"="asc"})
 * })
 */
class CmsAddress
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     */
    public $country;

    /**
     * @ODM\Field(type="string")
     */
    public $zip;

    /**
     * @ODM\Field(type="string")
     */
    public $city;

    /**
     * @ODM\ReferenceOne(targetDocument="CmsUser")
     */
    public $user;

    public function getId() {
        return $this->id;
    }
    
    public function getUser() {
        return $this->user;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getZipCode() {
        return $this->zip;
    }

    public function getCity() {
        return $this->city;
    }
    
    public function setUser(CmsUser $user) {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}
