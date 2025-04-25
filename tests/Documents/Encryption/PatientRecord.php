<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;

#[EmbeddedDocument]
class PatientRecord
{
    #[ODM\Id]
    public ?string $id;

    #[ODM\Field]
    #[ODM\Encrypt(queryType: ODM\Encrypt::QUERY_TYPE_EQUALITY)]
    public string $ssn;

    #[ODM\EmbedOne(targetDocument: PatientBilling::class)]
    #[ODM\Encrypt]
    public PatientBilling $billing;

    #[ODM\Field]
    #[ODM\Encrypt(queryType: ODM\Encrypt::QUERY_TYPE_RANGE, sparsity: 1, trimFactor: 4, min: 100, max: 2000)]
    public int $billingAmount;
}
