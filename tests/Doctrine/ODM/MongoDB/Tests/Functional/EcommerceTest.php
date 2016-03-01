<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\StockItem;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;

class EcommerceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $currencies = array('USD' => 1, 'EURO' => 1.7, 'JPN' => 0.0125);

        foreach ($currencies as $name => &$multiplier) {
            $multiplier = new Currency($name, $multiplier);
            $this->dm->persist($multiplier);
        }

        $product = new ConfigurableProduct('T-Shirt');
        $product->addOption(new Option('small', new Money(12.99, $currencies['USD']), new StockItem('T-shirt Size S', new Money(9.99, $currencies['USD']), 15)));
        $product->addOption(new Option('medium', new Money(14.99, $currencies['USD']), new StockItem('T-shirt Size M', new Money(11.99, $currencies['USD']), 15)));
        $product->addOption(new Option('large', new Money(17.99, $currencies['USD']), new StockItem('T-shirt Size L', new Money(13.99, $currencies['USD']), 15)));

        $this->dm->persist($product);
        $this->dm->flush();
        foreach ($currencies as $currency) {
            $this->dm->detach($currency);
        }
        $this->dm->detach($product);

        unset($currencies, $product);
    }

    public function testEmbedding()
    {
        $product = $this->getProduct();
        $price =  $product->getOption('small')->getPrice(true);
        $currency = $price->getCurrency();
        $this->assertTrue($currency instanceof Currency);
        $this->assertEquals(3, count($product->getOptions()));
        $this->assertEquals(12.99, $product->getOption('small')->getPrice());

        $usdCurrency = $this->dm->getRepository('Documents\Ecommerce\Currency')->findOneBy(array('name' => 'USD'));
        $this->assertNotNull($usdCurrency);
        $usdCurrency->setMultiplier('2');

        $this->assertTrue($product->getOption('small')->getStockItem() instanceof \Documents\Ecommerce\StockItem);
        $this->assertNotNull($product->getOption('small')->getStockItem()->getId());
        $this->assertEquals(12.99 * 2, $product->getOption('small')->getPrice());
    }

    public function testMoneyDocumentsAvailableForReference()
    {
        $product = $this->getProduct();
        $price =  $product->getOption('small')->getPrice(true);
        $currency = $price->getCurrency();
        $this->assertTrue($currency instanceof Currency);
        $this->assertNotNull($currency->getId());
        $this->assertEquals($currency, $this->dm->getRepository('Documents\Ecommerce\Currency')->findOneBy(array('name' => Currency::USD)));
    }

    public function testRemoveOption()
    {
        $product = $this->getProduct();

        $this->assertEquals(3, count($product->getOptions()));
        $product->removeOption('small');
        $this->assertEquals(2, count($product->getOptions()));
        $this->dm->flush();
        $this->dm->detach($product);
        unset($product);
        $this->assertFalse(isset($product));

        $product = $this->getProduct();
        $this->assertEquals(2, count($product->getOptions()));
    }

    public function testDoesNotSaveTransientFields()
    {
        $product = $this->getProduct();

        $product->selectOption('small');
        $this->dm->flush();
    }

    protected function getProduct()
    {
        $products = $this->dm->getRepository('Documents\Ecommerce\ConfigurableProduct')
            ->createQueryBuilder()
            ->getQuery()
            ->execute();
        $products->valid() ?: $products->next();
        return $products->current();
    }
}
