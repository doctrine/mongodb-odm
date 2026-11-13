<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class PhoneNumber
{
    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $number,
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $label,
    ) {
    }
}
