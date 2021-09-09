<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass */
abstract class BaseEmployee
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="int", strategy="increment")
     *
     * @var int
     */
    protected $changes = 0;

    /**
     * @ODM\Field(type="collection")
     *
     * @var string[]
     */
    protected $notes = [];

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $name;

    /**
     * @ODM\Field(type="float")
     *
     * @var float|null
     */
    protected $salary;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    protected $started;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    protected $left;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class)
     *
     * @var Address|null
     */
    protected $address;

    public function getId()
    {
        return $this->id;
    }

    public function setId($val): BaseEmployee
    {
        $this->id = $val;

        return $this;
    }

    public function getChanges(): int
    {
        return $this->changes;
    }

    public function incrementChanges($num): BaseEmployee
    {
        $this->changes += $num;

        return $this;
    }

    public function getNotes(): array
    {
        return $this->notes;
    }

    public function addNote($note): BaseEmployee
    {
        $this->notes[] = $note;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($val): BaseEmployee
    {
        $this->name = $val;

        return $this;
    }

    public function getSalary()
    {
        return $this->salary;
    }

    public function setSalary($val): BaseEmployee
    {
        $this->salary = $val;

        return $this;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function setStarted($val): BaseEmployee
    {
        $this->started = $val;

        return $this;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setLeft($val): BaseEmployee
    {
        $this->left = $val;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($val): BaseEmployee
    {
        $this->address = $val;

        return $this;
    }
}
