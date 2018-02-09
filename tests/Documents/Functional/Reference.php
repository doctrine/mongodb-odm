<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Reference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}
