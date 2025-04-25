<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;

#[EmbeddedDocument]
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
