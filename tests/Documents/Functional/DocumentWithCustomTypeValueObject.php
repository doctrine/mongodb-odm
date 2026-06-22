<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DocumentWithCustomTypeValueObject
{

    #[ODM\Id]
    public ?string $id;

    #[ODM\Field(type: 'custom_value_object_child')]
    public ValueObjectChild $child;

}
