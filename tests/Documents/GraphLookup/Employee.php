<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Employee
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="ref")
     *
     * @var Employee|null
     */
    public $reportsTo;

    /**
     * @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="id")
     *
     * @var Employee|null
     */
    public $reportsToId;

    /**
     * @ODM\ReferenceMany(targetDocument=Employee::class, mappedBy="reportsTo")
     *
     * @var Collection<int, Employee>
     */
    public $reportingEmployees;

    public function __construct(string $name, ?Employee $reportsTo = null)
    {
        $this->name               = $name;
        $this->reportsTo          = $reportsTo;
        $this->reportsToId        = $reportsTo;
        $this->reportingEmployees = new ArrayCollection();

        if (! $reportsTo) {
            return;
        }

        $reportsTo->reportingEmployees[] = $this;
    }
}
