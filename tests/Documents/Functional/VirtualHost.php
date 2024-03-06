<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'test_functional_virtual_host')]
class VirtualHost
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var VirtualHostDirective|null */
    #[ODM\EmbedOne(targetDocument: VirtualHostDirective::class)]
    protected $vhostDirective;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getVHostDirective(): VirtualHostDirective
    {
        if (! $this->vhostDirective) {
            $this->vhostDirective = new VirtualHostDirective('VirtualHost', '*:80');
        }

        return $this->vhostDirective;
    }

    public function setVHostDirective(VirtualHostDirective $value): VirtualHost
    {
        $this->vhostDirective = $value;

        return $this;
    }
}
