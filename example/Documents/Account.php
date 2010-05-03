<?php

namespace Documents;

/** @Document */
class Account
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    public function getId()
    {
        return $this->id;
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