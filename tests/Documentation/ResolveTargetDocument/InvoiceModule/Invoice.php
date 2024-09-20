<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument\InvoiceModule;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

#[Document]
class Invoice
{
    #[Id]
    public string $id;

    #[ReferenceOne]
    public InvoiceSubjectInterface $subject;
}
