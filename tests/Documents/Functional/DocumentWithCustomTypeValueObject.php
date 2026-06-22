<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DocumentWithCustomTypeValueObject
{

    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\Field(type: 'custom_value_object_child')]
    public ValueObjectChild $child;

}
