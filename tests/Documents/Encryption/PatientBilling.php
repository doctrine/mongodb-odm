<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\EmbeddedDocument]
class PatientBilling
{
    public function __construct(
        #[ODM\Field]
        public string $type,
        #[ODM\Field]
        public string $number,
    ) {
    }
}
