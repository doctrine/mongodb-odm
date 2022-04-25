<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;
use Documents\Ecommerce\StockItem;

class EcommerceTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $currencies  = [];
        $multipliers = ['USD' => 1, 'EURO' => 1.7, 'JPN' => 0.0125];

        foreach ($multipliers as $currencyName => $multiplier) {
            $currency = new Currency($currencyName, $multiplier);
            $this->dm->persist($currency);

            $currencies[$currencyName] = $currency;
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

    public function testEmbedding(): void
    {
        $product  = $this->getProduct();
        $price    =  $product->getOption('small')->getPrice(true);
        $currency = $price->getCurrency();
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertCount(3, $product->getOptions());
        $this->assertEquals(12.99, $product->getOption('small')->getPrice());

        $usdCurrency = $this->dm->getRepository(Currency::class)->findOneBy(['name' => 'USD']);
        $this->assertNotNull($usdCurrency);
        $usdCurrency->setMultiplier('2');

        $this->assertInstanceOf(StockItem::class, $product->getOption('small')->getStockItem());
        $this->assertNotNull($product->getOption('small')->getStockItem()->getId());
        $this->assertEquals(12.99 * 2, $product->getOption('small')->getPrice());
    }

    public function testMoneyDocumentsAvailableForReference(): void
    {
        $product  = $this->getProduct();
        $price    =  $product->getOption('small')->getPrice(true);
        $currency = $price->getCurrency();
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertNotNull($currency->getId());
        $this->assertEquals($currency, $this->dm->getRepository(Currency::class)->findOneBy(['name' => Currency::USD]));
    }

    public function testRemoveOption(): void
    {
        $product = $this->getProduct();

        $this->assertCount(3, $product->getOptions());
        $product->removeOption('small');
        $this->assertCount(2, $product->getOptions());
        $this->dm->flush();
        $this->dm->detach($product);
        unset($product);

        $product = $this->getProduct();
        $this->assertCount(2, $product->getOptions());
    }

    protected function getProduct(): ConfigurableProduct
    {
        $products = $this->dm->getRepository(ConfigurableProduct::class)
            ->createQueryBuilder()
            ->getQuery()
            ->execute();

        $this->assertInstanceOf(Iterator::class, $products);

        $products->valid() ?: $products->next();

        return $products->current();
    }
}
