<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\StockItem;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;
use Documents\User;
use Documents\Event;

class MapReduceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $currencies = array('USD' => 1, 'EURO' => 1.7, 'JPN' => 0.0125);

        foreach ($currencies as $name => &$multiplier) {
            $multiplier = new Currency($name, $multiplier);
            $this->dm->persist($multiplier);
        }

        $stockItems = array(
            new StockItem('stock_item_0', new Money(9.99 * 0 + 5, $currencies['USD']), 5),
            new StockItem('stock_item_1', new Money(9.99 * 1 + 5, $currencies['USD']), 15 * 1 - 4),
            new StockItem('stock_item_2', new Money(9.99 * 2 + 5, $currencies['USD']), 15 * 2 - 4),
            new StockItem('stock_item_3', new Money(9.99 * 3 + 5, $currencies['USD']), 15 * 3 - 4),
            new StockItem('stock_item_4', new Money(9.99 * 4 + 5, $currencies['USD']), 15 * 4 - 4),
            new StockItem('stock_item_5', new Money(9.99 * 5 + 5, $currencies['USD']), 15 * 5 - 4),
            new StockItem('stock_item_6', new Money(9.99 * 6 + 5, $currencies['USD']), 15 * 6 - 4),
            new StockItem('stock_item_7', new Money(9.99 * 7 + 5, $currencies['USD']), 15 * 7 - 4),
            new StockItem('stock_item_8', new Money(9.99 * 8 + 5, $currencies['USD']), 15 * 8 - 4),
            new StockItem('stock_item_9', new Money(9.99 * 9 + 5, $currencies['USD']), 15 * 9 - 4),
        );

        $options = array(
            new Option('option_0', new Money(13.99, $currencies['USD']), $stockItems[0]),
            new Option('option_1', new Money(14.99, $currencies['USD']), $stockItems[1]),
            new Option('option_2', new Money(15.99, $currencies['USD']), $stockItems[2]),
            new Option('option_3', new Money(16.99, $currencies['USD']), $stockItems[3]),
            new Option('option_4', new Money(17.99, $currencies['USD']), $stockItems[4]),
            new Option('option_5', new Money(18.99, $currencies['USD']), $stockItems[5]),
            new Option('option_6', new Money(19.99, $currencies['USD']), $stockItems[6]),
            new Option('option_7', new Money(20.99, $currencies['USD']), $stockItems[7]),
            new Option('option_8', new Money(21.99, $currencies['USD']), $stockItems[8]),
            new Option('option_9', new Money(22.99, $currencies['USD']), $stockItems[9]),
        );

        $products = array(
            new ConfigurableProduct('product_0'),
            new ConfigurableProduct('product_1'),
            new ConfigurableProduct('product_2'),
            new ConfigurableProduct('product_3'),
            new ConfigurableProduct('product_4'),
            new ConfigurableProduct('product_5'),
            new ConfigurableProduct('product_6'),
            new ConfigurableProduct('product_7'),
            new ConfigurableProduct('product_8'),
            new ConfigurableProduct('product_9'),
        );

        $products[0]->addOption($options[0]);
        $products[0]->addOption($options[4]);
        $products[0]->addOption($options[6]);

        $products[1]->addOption($options[1]);
        $products[1]->addOption($options[2]);
        $products[1]->addOption($options[5]);
        $products[1]->addOption($options[7]);
        $products[1]->addOption($options[8]);

        $products[2]->addOption($options[3]);
        $products[2]->addOption($options[5]);
        $products[2]->addOption($options[7]);
        $products[2]->addOption($options[9]);

        $products[3]->addOption($options[0]);
        $products[3]->addOption($options[1]);
        $products[3]->addOption($options[2]);
        $products[3]->addOption($options[3]);
        $products[3]->addOption($options[4]);
        $products[3]->addOption($options[5]);

        $products[4]->addOption($options[4]);
        $products[4]->addOption($options[7]);
        $products[4]->addOption($options[2]);
        $products[4]->addOption($options[8]);

        $products[5]->addOption($options[9]);

        $products[6]->addOption($options[7]);
        $products[6]->addOption($options[8]);
        $products[6]->addOption($options[9]);

        $products[7]->addOption($options[4]);
        $products[7]->addOption($options[5]);

        $products[8]->addOption($options[2]);

        $products[9]->addOption($options[4]);
        $products[9]->addOption($options[3]);
        $products[9]->addOption($options[7]);

        foreach ($products as $product) {
            $this->dm->persist($product);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testMapReduce()
    {
        $map = 'function() {
            for(i = 0; i <= this.options.length; i++) {
                emit(this.name, { count: 1 });
            }
        }';

        $reduce = 'function(product, values) {
            var total = 0
            values.forEach(function(value){
                total+= value.count;
            });
            return {
                product: product,
                options: total,
                test: values
            };
        }';

        $cursor = $this->dm->createQueryBuilder('Documents\Ecommerce\ConfigurableProduct')
            ->map($map)->reduce($reduce)
            ->getQuery()->execute();
        $this->assertEquals(10, $cursor->count());

        $qb = $this->dm->createQueryBuilder('Documents\Ecommerce\ConfigurableProduct')
            ->mapReduce($map, $reduce);
        $query = $qb->getQuery();
        $cursor = $query->execute();
        $this->assertEquals(10, $cursor->count());
        $results = $cursor->toArray();
        $this->assertTrue(is_array($results[0]));
    }

    public function testMapReduce2()
    {
        $user = new User();
        $user->setUsername('bob');

        $event1 = new Event();
        $event1->setUser($user);
        $event1->setType('sale');
        $event1->setTitle('Test 1');

        $event2 = new Event();
        $event2->setUser($user);
        $event2->setType('sale');
        $event2->setTitle('Test 2');

        $event3 = new Event();
        $event3->setUser($user);
        $event3->setType('sale');
        $event3->setTitle('Test 2');

        $this->dm->persist($user);
        $this->dm->persist($event1);
        $this->dm->persist($event2);
        $this->dm->persist($event3);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\Event')
            ->field('type')
            ->equals('sale')
            ->map('function() { emit(this.user.$id, 1); }')
            ->reduce("function(k, vals) {
                var sum = 0;
                for (var i in vals) {
                    sum += vals[i];
                }
                return sum;
            }");
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();

        $this->assertEquals($user->getId(), (string) $user2['_id']);
        $this->assertEquals(3, $user2['value']);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMapReduceError()
    {
        $map = 'function() {
            for(i = 0; i <= this.options.length; i++) {
                emit(this.name.fetch(), { count: 1 });
            }
        }';

        $reduce = 'function(product, values) {
            var total = 0
            values.forEach(function(value){
                total += value.count;
            });
            return {
                product: product,
                options: total,
                test: values
            };
        }';

        $results = $this->dm->createQueryBuilder('Documents\Ecommerce\ConfigurableProduct')
            ->map($map)->reduce($reduce)
            ->getQuery()->execute();
    }
}
