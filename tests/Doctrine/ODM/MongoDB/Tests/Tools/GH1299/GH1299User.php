<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH1299;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class GH1299User extends BaseUser
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $lastname;
}
