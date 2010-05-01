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