<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\EmbeddedDocument]
class Insurance
{
    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $provider,
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $policyNumber,
    ) {
    }
}
