<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use function uniqid;

/** @ODM\EmbeddedDocument */
class VirtualHostDirective
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $recId;
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $name;
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $value;
    /**
     * @ODM\EmbedMany(targetDocument=Documents\Functional\VirtualHostDirective::class)
     *
     * @var Collection<int, VirtualHostDirective>|null
     */
    protected $directives;

    public function __construct(string $name = '', string $value = '')
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->name . ' ' . $this->value;
    }

    public function getRecId(): ?string
    {
        return $this->recId;
    }

    public function setRecId(?string $value = null): void
    {
        if (! $value) {
            $value = uniqid();
        }

        $this->recId = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /** @return Collection<int, VirtualHostDirective> */
    public function getDirectives(): Collection
    {
        if (! $this->directives) {
            $this->directives = new ArrayCollection([]);
        }

        return $this->directives;
    }

    /** @param Collection<int, VirtualHostDirective> $value */
    public function setDirectives(Collection $value): VirtualHostDirective
    {
        $this->directives = $value;

        return $this;
    }

    public function addDirective(VirtualHostDirective $d): VirtualHostDirective
    {
        $this->getDirectives()->add($d);

        return $this;
    }

    public function hasDirective(string $name): ?VirtualHostDirective
    {
        foreach ($this->getDirectives() as $d) {
            if ($d->getName() === $name) {
                return $d;
            }
        }

        return null;
    }

    public function getDirective(string $name): ?VirtualHostDirective
    {
        return $this->hasDirective($name);
    }

    public function removeDirective(VirtualHostDirective $d): VirtualHostDirective
    {
        $this->getDirectives()->removeElement($d);

        return $this;
    }
}
