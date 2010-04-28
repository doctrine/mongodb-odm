<?php

namespace Entities;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Entity */
class Account
{
    /** @Field */
    private $id;

    /** @Field */
    private $name;

    public function getId()
    {
        return $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}