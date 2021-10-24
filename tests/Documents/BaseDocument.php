<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class BaseDocument
{
    /** @var bool */
    public $persisted = false;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $inheritedProperty;

    public function setInheritedProperty(string $value): void
    {
        $this->inheritedProperty = $value;
    }

    public function getInheritedProperty(): ?string
    {
        return $this->inheritedProperty;
    }

    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->persisted = true;
    }
}
