<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class EmailAddress
{
    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $address,
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $label,
    ) {
    }
}
