<?php

namespace Entities;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Entity */
class Profile
{
    /** @Field */
    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}