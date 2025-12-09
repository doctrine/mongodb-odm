<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Employee
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Employee|null */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: ['persist'], storeAs: 'ref')]
    public $reportsTo;

    /** @var Employee|null */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: ['persist'], storeAs: 'id')]
    public $reportsToId;

    /** @var Collection<int, Employee> */
    #[ODM\ReferenceMany(targetDocument: self::class, mappedBy: 'reportsTo')]
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
