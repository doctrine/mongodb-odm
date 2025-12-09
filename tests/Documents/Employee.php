<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Employee extends BaseEmployee
{
    /** @var Manager|null */
    #[ODM\ReferenceOne(targetDocument: Manager::class)]
    private $manager;

    public function getManager(): ?Manager
    {
        return $this->manager;
    }

    public function setManager(Manager $val): Employee
    {
        $this->manager = $val;

        return $this;
    }
}
