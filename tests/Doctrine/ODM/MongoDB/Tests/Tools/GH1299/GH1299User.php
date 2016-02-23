<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH1299;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\Tools\GH1299\BaseUser;

/** @ODM\Document */
class GH1299User extends BaseUser
{
    /** @ODM\Field(type="string") */
    protected $lastname;
}
