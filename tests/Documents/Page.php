<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Page
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="int") */
    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }
}
