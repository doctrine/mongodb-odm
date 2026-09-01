<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;

#[ODM\EmbeddedDocument]
class PatientRecord
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt(queryType: EncryptQuery::Equality)]
        public string $ssn,
        #[ODM\EmbedOne(targetDocument: PatientBilling::class)]
        #[ODM\Encrypt]
        public PatientBilling $billing,
        #[ODM\Field]
        #[ODM\Encrypt(queryType: EncryptQuery::Range, sparsity: 1, trimFactor: 4, min: 100, max: 2000)]
        public int $billingAmount,
    ) {
    }
}
