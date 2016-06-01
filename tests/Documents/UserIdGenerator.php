<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 */
class UserIdGenerator extends BaseDocument
{
    /** @ODM\Id(strategy="CUSTOM", type="string", options={"class"="\Documents\IdGenerator\UserIdGenerator"}) */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $username;
    
    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return the $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

}
