<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass */
abstract class BaseEmployee
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Increment */
    protected $changes = 0;

    /** @ODM\Collection */
    protected $notes = array();

    /** @ODM\String */
    protected $name;

    /** @ODM\Float */
    protected $salary;

    /** @ODM\Date */
    protected $started;

    /** @ODM\Date */
    protected $left;

    /** @ODM\EmbedOne(targetDocument="Address") */
    protected $address;

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function incrementChanges($num)
    {
        $this->changes = $this->changes + $num;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function addNote($note)
    {
        $this->notes[] = $note;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($val)
    {
        $this->name = $val;
        return $this;
    }

    public function getSalary()
    {
        return $this->salary;
    }

    public function setSalary($val)
    {
        $this->salary = $val;
        return $this;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function setStarted($val)
    {
        $this->started = $val;
        return $this;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setLeft($val)
    {
        $this->left = $val;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($val)
    {
        $this->address = $val;
        return $this;
    }
}