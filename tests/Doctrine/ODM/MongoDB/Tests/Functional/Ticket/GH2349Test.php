<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents74\GH2349Customer;
use Documents74\GH2349Order;

/** @requires PHP 7.4 */
class GH2349Test extends BaseTestCase
{
    public function testAccessingUnitializedProperties(): void
    {
        $customer = new GH2349Customer('Some Place');
        $order    = new GH2349Order($customer);

        $this->dm->persist($customer);
        $this->dm->persist($order);
        $this->dm->flush();
        $this->dm->clear();

        $orderId = GH2349Order::ID;

        $order = $this->dm->getRepository(GH2349Order::class)->find($orderId);

        // Fetch a list of Customers from the DB. Any customer object that was referenced in the above order is still
        // a proxy object, however it will not have the defaults set for the un-managed $domainEvents property.
        $customers = $this->dm->getRepository(GH2349Customer::class)->findAll();

        foreach ($customers as $customer) {
            self::assertSame([], $customer->getEvents()); // This would trigger an error if unmapped properties are unset
        }
    }
}
