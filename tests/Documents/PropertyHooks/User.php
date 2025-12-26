<?php

declare(strict_types=1);

namespace Documents\PropertyHooks;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use ValueError;

#[Document(collection: 'property_hooks_user')]
class User
{
    // phpcs:disable
    #[Id]
    public ?string $id;

    #[Field]
    public string $first {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->first = $value;
        }
    }

    #[Field]
    public string $last {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->last = $value;
        }
    }

    public string $fullName {
        get => $this->first . " " . $this->last;
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    #[Field]
    public string $language = 'de' {
        // Override the "read" action with arbitrary logic.
        get => strtoupper($this->language);

        // Override the "write" action with arbitrary logic.
        set {
            $this->language = strtolower($value);
        }
    }
    // phpcs:enable
}
