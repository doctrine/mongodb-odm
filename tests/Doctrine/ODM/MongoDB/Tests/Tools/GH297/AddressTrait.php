<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH297;

trait AddressTrait
{
    /**
     * @ODM\EmbedOne
     *
     * @var Address|null
     */
    private $address;

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
