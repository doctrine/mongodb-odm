<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH1178;

class UserId
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
