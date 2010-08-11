<?php

namespace Documents;

/** @Document */
class ForumUser
{
    /** @Id */
    public $id;

    /** @String */
    public $username;

    /** @ReferenceOne(targetDocument="ForumAvatar", cascade={"persist"}) */
    public $avatar;
    
    public function getId()
    {
    	return $this->id;
    }
    
    public function getUsername()
    {
    	return $this->username;
    }
    
    public function getAvatar()
    {
    	return $this->avatar;
    }
    
    public function setAvatar(ForumAvatar $avatar)
    {
    	$this->avatar = $avatar;
    }
}