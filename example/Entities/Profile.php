<?php

namespace Entities;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class Profile
{
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