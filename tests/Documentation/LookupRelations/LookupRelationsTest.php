<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use function array_map;
use function iterator_to_array;

class LookupRelationsTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $items = array_map(function ($name) {
            $item = new Item($name);
            $this->dm->persist($item);

            return $item;
        }, ['Wheel', 'Gravel bike', 'Handlebars', 'Sattle', 'Pedals']);

        $user1 = new User('Jacques Anquetil');
        $user2 = new User('Eddy Merckx');
        $this->dm->persist($user1);
        $this->dm->persist($user2);

        $order       = new Order();
        $order->date = new DateTimeImmutable('1982-09-01');
        $order->user = $user1;
        $order->items->add($items[0]);
        $order->items->add($items[2]);
        $order->items->add($items[4]);
        $this->dm->persist($order);

        // Empty order
        $order       = new Order();
        $order->date = new DateTimeImmutable('1974-07-01');
        $order->user = $user1;
        $this->dm->persist($order);

        $order       = new Order();
        $order->date = new DateTimeImmutable('1965-05-01');
        $order->user = $user2;
        $order->items->add($items[0]);
        $this->dm->persist($order);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testLookupReference(): void
    {
        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->sort('date', 'asc')
            ->lookup('items')->alias('items')
            ->lookup('user')->alias('user')
            ->unwind('$user')
            ->getAggregation();

        $results = iterator_to_array($aggregation);

        $this->assertCount(3, $results);
        $this->assertIsArray($results[0]);
        $this->assertInstanceOf(UTCDateTime::class, $results[0]['date']);
        $this->assertIsArray($results[0]['items']);
        $this->assertSame('Wheel', $results[0]['items'][0]['name']);
        $this->assertIsArray($results[0]['user']);
        $this->assertSame('Eddy Merckx', $results[0]['user']['name']);
    }

    public function testLookupReferenceWithHydratation(): void
    {
        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->hydrate(OrderResult::class)
            ->sort('date', 'asc')
            ->lookup('items')
                ->alias('items')
            ->lookup('user')
                ->alias('user')
            ->unwind('$user')
            ->getAggregation();

        $results = iterator_to_array($aggregation);

        $this->assertCount(3, $results);
        $this->assertInstanceOf(OrderResult::class, $results[0]);
        $this->assertSame('Wheel', $results[0]->items[0]->name);
        $this->assertSame('Eddy Merckx', $results[0]->user->name);
    }

    public function testLookupReverseReference(): void
    {
        $aggregation = $this->dm->createAggregationBuilder(User::class)
            ->sort('name', 'asc')
            ->lookup('Order')
                ->alias('orders')
                ->localField('id')
                ->foreignField('user')
            ->getAggregation();

        $results = iterator_to_array($aggregation);

        $this->assertCount(2, $results);
        $this->assertIsArray($results[0]);
        $this->assertSame('Eddy Merckx', $results[0]['name']);
        $this->assertIsArray($results[0]['orders']);
        $this->assertCount(1, $results[0]['orders']);
        $this->assertInstanceOf(ObjectId::class, $results[0]['orders'][0]['items'][0]);
    }

    public function testLookupSecondLevelWithHydratation(): void
    {
        $aggregation = $this->dm->createAggregationBuilder(User::class)
            ->hydrate(UserResult::class)

            // Lookup for the orders of the user
            ->lookup('Order')
                ->alias('orders')
                ->localField('_id')
                ->foreignField('user')

            // Unwind orders so we can use $lookup on the order items
            ->unwind('$orders')
                ->preserveNullAndEmptyArrays(true)

            // Look up the order's items, replacing the references in the order
            ->lookup('Item')
                ->alias('orders.items')
                ->localField('orders.items')
                ->foreignField('_id')

            // Group the orders back by user
            ->group()
                ->field('id')->expression('$_id')
                ->field('root')->first('$$ROOT')
                ->field('orders')->push('$orders')

            // Use $mergeObjects to merge all fields from the document with the
            // order list (with looked up items)
            ->replaceRoot()
                ->mergeObjects([
                    '$root',
                    ['orders' => '$orders'],
                ])

            // Sort for predictable results
            ->sort('name', 'asc')
            ->getAggregation();

        $results = iterator_to_array($aggregation);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(UserResult::class, $results[0]);
        $this->assertSame('Eddy Merckx', $results[0]->name);
        $this->assertCount(1, $results[0]->orders);
        $this->assertSame('Wheel', $results[0]->orders[0]->items[0]->name);
    }
}
