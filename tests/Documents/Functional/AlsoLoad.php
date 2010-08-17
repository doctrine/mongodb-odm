<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class AlsoLoad
{
    /** @Id */
    public $id;

    /**
     * @String
     * @AlsoLoad({"bar", "zip"})
     */
    public $foo;

    /** @NotSaved */
    public $bar;

    /** @NotSaved */
    public $zip;

    /** @NotSaved */
    public $name;

    /** @NotSaved */
    public $fullName;

    /** @String */
    public $firstName;

    /** @String */
    public $lastName;

    /** @String */
    public $test;

    /** @String */
    public $test1;

    /** @String */
    public $test2;

    /** @AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        $e = explode(' ', $name);
        $this->firstName = $e[0];
        $this->lastName = $e[1];
    }

    /** @AlsoLoad({"test1", "test2"}) */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}