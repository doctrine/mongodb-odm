<?php

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\QueryResultDocument
 */
class ReportingHierarchy
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="ref") */
    public $reportsTo;

    /** @ODM\ReferenceOne(targetDocument=Employee::class, cascade={"persist"}, storeAs="id") */
    public $reportsToId;

    /** @ODM\EmbedMany(targetDocument=Employee::class) */
    public $reportingHierarchy;

    public function __construct()
    {
        $this->reportingHierarchy = new ArrayCollection();
    }
}
