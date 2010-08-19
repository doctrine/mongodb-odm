<?php

namespace Documents;

/**
 * @Document
 * @Indexes({
 *   @Index(keys={"country"="asc", "zip"="asc", "city"="asc"})
 * })
 */
class CmsAddress
{
    /**
     * @Id
     */
    public $id;

    /**
     * @String
     */
    public $country;

    /**
     * @String
     */
    public $zip;

    /**
     * @String
     */
    public $city;

    /**
     * @ReferenceOne(targetDocument="CmsUser")
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