<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH1299;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
#[ODM\Document]
class BaseUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    protected $name;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
