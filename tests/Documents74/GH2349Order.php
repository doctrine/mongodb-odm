<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document() */
class GH2349Order
{
    public const ID = '610419a277d50f7609139543';

    /** @ODM\Id() */
    private string $id;

    /** @ODM\ReferenceOne(targetDocument=GH2349Customer::class, storeAs="ref") */
    private GH2349Customer $customer;

    public function __construct(GH2349Customer $customer)
    {
        $this->id       = (string) new ObjectId(self::ID);
        $this->customer = $customer;
    }
}
