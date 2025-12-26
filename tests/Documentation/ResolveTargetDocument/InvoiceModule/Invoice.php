<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument\InvoiceModule;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne;

#[Document]
class Invoice
{
    #[Id]
    public string $id;

    #[ReferenceOne]
    public InvoiceSubjectInterface $subject;
}
