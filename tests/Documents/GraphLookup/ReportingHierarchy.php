<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\QueryResultDocument */
class ReportingHierarchy
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
     * @var string|null
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
     * @ODM\EmbedMany(targetDocument=Employee::class)
     *
     * @var Collection<int, Employee>
     */
    public $reportingHierarchy;

    public function __construct()
    {
        $this->reportingHierarchy = new ArrayCollection();
    }
}
