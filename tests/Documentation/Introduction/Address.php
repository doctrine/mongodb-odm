<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Address
{
    public function __construct(
        #[ODM\Field]
        public string $address,
        #[ODM\Field]
        public string $city,
        #[ODM\Field]
        public string $state,
        #[ODM\Field]
        public string $zipcode,
    ) {
    }
}
