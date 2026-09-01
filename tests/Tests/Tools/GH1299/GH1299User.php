<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH1299;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class GH1299User extends BaseUser
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $lastname;
}
