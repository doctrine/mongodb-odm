<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\QueryResultDocument]
class ReportingHierarchy
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Employee|null */
    #[ODM\ReferenceOne(targetDocument: Employee::class, cascade: ['persist'], storeAs: 'ref')]
    public $reportsTo;

    /** @var Employee|null */
    #[ODM\ReferenceOne(targetDocument: Employee::class, cascade: ['persist'], storeAs: 'id')]
    public $reportsToId;

    /** @var Collection<int, Employee> */
    #[ODM\EmbedMany(targetDocument: Employee::class)]
    public $reportingHierarchy;

    public function __construct()
    {
        $this->reportingHierarchy = new ArrayCollection();
    }
}
