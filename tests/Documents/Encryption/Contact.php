<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Contact
{
    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $email,
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $phone,
    ) {
    }
}
