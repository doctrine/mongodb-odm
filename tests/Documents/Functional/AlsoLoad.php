<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class AlsoLoad
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\String
     * @ODM\AlsoLoad({"bar", "zip"})
     */
    public $foo;

    /** @ODM\NotSaved */
    public $bar;

    /** @ODM\NotSaved */
    public $zip;

    /** @ODM\NotSaved */
    public $name;

    /** @ODM\NotSaved */
    public $fullName;

    /** @ODM\String */
    public $firstName;

    /** @ODM\String */
    public $lastName;

    /** @ODM\String */
    public $test;

    /** @ODM\String */
    public $test1;

    /** @ODM\String */
    public $test2;

    /** @ODM\AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        $e = explode(' ', $name);
        $this->firstName = $e[0];
        $this->lastName = $e[1];
    }

    /** @ODM\AlsoLoad({"test1", "test2"}) */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}