<?php

namespace Documents;

/**
 * @Document
 */
class CmsPhonenumber
{
    /** @Id */
    public $phonenumber;

    /**
     * @ReferenceOne(targetDocument="CmsUser", cascade={"merge"})
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
    }
    
    public function getUser() {
        return $this->user;
    }
}
