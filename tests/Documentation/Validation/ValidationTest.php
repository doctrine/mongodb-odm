<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\Driver\Exception\ServerException;

class ValidationTest extends BaseTestCase
{
    public function testLifecycleValidation(): void
    {
        $customer = new Customer(orderLimit: 100);
        $this->dm->persist($customer);
        $this->dm->flush();

        // Invalid order
        $order1 = new Order(customer: $customer);
        $order1->orderLines->add(new OrderLine(50));
        $order1->orderLines->add(new OrderLine(60));

        try {
            $this->dm->persist($order1);
            $this->dm->flush();
            $this->fail('Expected CustomerOrderLimitExceededException');
        } catch (CustomerOrderLimitExceededException) {
            // Expected
            $this->dm->clear();
        }

        // Order should not have been saved
        $order1 = $this->dm->find(Order::class, $order1->id);
        $this->assertNull($order1);

        // Valid order
        $customer = new Customer(orderLimit: 100);
        $order2   = new Order(customer: $customer);
        $order2->orderLines->add(new OrderLine(50));
        $order2->orderLines->add(new OrderLine(40));
        $this->dm->persist($customer);
        $this->dm->persist($order2);
        $this->dm->flush();
        $this->dm->clear();

        // Update order to exceed limit
        $order2 = $this->dm->find(Order::class, $order2->id);
        $order2->orderLines->add(new OrderLine(20));

        try {
            $this->dm->flush();
            $this->fail('Expected CustomerOrderLimitExceededException');
        } catch (CustomerOrderLimitExceededException) {
            // Expected
            $this->dm->clear();
        }

        $order2 = $this->dm->find(Order::class, $order2->id);
        $this->assertCount(2, $order2->orderLines, 'Order should not have been updated');
    }

    public function testSchemaValidation(): void
    {
        $this->dm->getSchemaManager()->createDocumentCollection(SchemaValidated::class);

        // Valid document
        $document         = new SchemaValidated();
        $document->name   = 'Jone Doe';
        $document->email  = 'jone.doe@example.com';
        $document->phone  = '123-456-7890';
        $document->status = 'Unknown';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        // Invalid document
        $document         = new SchemaValidated();
        $document->email  = 'foo';
        $document->status = 'Invalid';

        $this->dm->persist($document);

        $this->expectException(ServerException::class);
        $this->dm->flush();
    }
}
