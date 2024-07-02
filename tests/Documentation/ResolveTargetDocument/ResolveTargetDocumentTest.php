<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;
use Documentation\ResolveTargetDocument\InvoiceModule\Invoice;
use Documentation\ResolveTargetDocument\InvoiceModule\InvoiceSubjectInterface;

class ResolveTargetDocumentTest extends BaseTestCase
{
    public function testTest(): void
    {
        $evm  = $this->dm->getEventManager();
        $rtdl = new ResolveTargetDocumentListener();

        // Adds a target-document class
        $rtdl->addResolveTargetDocument(
            InvoiceSubjectInterface::class,
            Customer::class,
            [],
        );

        $evm->addEventSubscriber($rtdl);

        $customer         = new Customer();
        $customer->name   = 'Example Customer';
        $invoice          = new Invoice();
        $invoice->subject = $customer;

        $this->dm->persist($customer);
        $this->dm->persist($invoice);
        $this->dm->flush();
        $this->dm->clear();

        $invoice = $this->dm->find(Invoice::class, $invoice->id);
        $this->assertInstanceOf(Customer::class, $invoice->subject);
        $this->assertSame('Example Customer', $invoice->subject->name);
    }
}
