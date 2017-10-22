<?php

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Employee
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="ref") */
    public $reportsTo;

    /** @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="id") */
    public $reportsToId;

    /** @ODM\ReferenceMany(targetDocument=Employee::class, mappedBy="reportsTo") */
    public $reportingEmployees;

    public function __construct($name, Employee $reportsTo = null)
    {
        $this->name = $name;
        $this->reportsTo = $reportsTo;
        $this->reportsToId = $reportsTo;
        $this->reportingEmployees = new ArrayCollection();

        if ($reportsTo) {
            $reportsTo->reportingEmployees[] = $this;
        }
    }
}
