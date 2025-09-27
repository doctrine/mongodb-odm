<?php // phpcs:ignoreFile

namespace Documents84\PropertyHooks;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document(collection: 'property_hooks_user')]
class MappingVirtualProperty
{
    #[Id]
    public ?string $id;

    #[Field]
    public string $first;

    #[Field]
    public string $last;

    #[Field]
    public string $fullName {
        get => $this->first . " " . $this->last;
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }
}
