<?php

namespace Documents;

/**
 * @Document
 */
class CmsGroup
{
    /**
     * @Id
     */
    public $id;
    /**
     * @String
     */
    public $name;
    /**
     * @ReferenceMany(targetDocument="CmsUser")
     */
    public $users;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addUser(CmsUser $user) {
        $this->users[] = $user;
    }

    public function getUsers() {
        return $this->users;
    }
}