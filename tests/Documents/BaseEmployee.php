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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $val): BaseEmployee
    {
        $this->id = $val;

        return $this;
    }

    public function getChanges(): int
    {
        return $this->changes;
    }

    public function incrementChanges(int $num): BaseEmployee
    {
        $this->changes += $num;

        return $this;
    }

    /** @return string[] */
    public function getNotes(): array
    {
        return $this->notes;
    }

    public function addNote(string $note): BaseEmployee
    {
        $this->notes[] = $note;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $val): BaseEmployee
    {
        $this->name = $val;

        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(float $val): BaseEmployee
    {
        $this->salary = $val;

        return $this;
    }

    public function getStarted(): ?DateTime
    {
        return $this->started;
    }

    public function setStarted(DateTime $val): BaseEmployee
    {
        $this->started = $val;

        return $this;
    }

    public function getLeft(): ?DateTime
    {
        return $this->left;
    }

    public function setLeft(DateTime $val): BaseEmployee
    {
        $this->left = $val;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $val): BaseEmployee
    {
        $this->address = $val;

        return $this;
    }
}
