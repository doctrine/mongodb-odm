<?php

declare(strict_types=1);

namespace Documents\Encrypted;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;

#[EmbeddedDocument]
class PatientRecord
{
    #[ODM\Id]
    public ?string $id;

    #[ODM\Field]
    #[ODM\Encrypt]
    public string $ssn;

    #[ODM\EmbedOne(targetDocument: PatientBilling::class)]
    #[ODM\Encrypt]
    public PatientBilling $billing;

    #[ODM\Field]
    public int $billingAmount;
}
