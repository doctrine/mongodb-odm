<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'same_collection')]
#[ODM\DiscriminatorField('type')]
#[ODM\DiscriminatorMap(['test1' => 'Documents\Functional\SameCollection1', 'test2' => 'Documents\Functional\SameCollection2'])]
#[ODM\DefaultDiscriminatorValue('test1')]
class SameCollection2
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var bool|null */
    #[ODM\Field(type: 'string')]
    public $ok;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $w00t;
}
