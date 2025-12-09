<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Documentation\ResolveTargetDocument\CustomerModule\Customer as BaseCustomer;
use Documentation\ResolveTargetDocument\InvoiceModule\InvoiceSubjectInterface;

#[Document]
class Customer extends BaseCustomer implements InvoiceSubjectInterface
{
    public function getName(): string
    {
        return $this->name;
    }
}
