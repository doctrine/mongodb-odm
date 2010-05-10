<?php

require_once 'TestInit.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Ecommerce\ConfigurableProduct,
    Documents\Ecommerce\StockItem,
    Documents\Ecommerce\Currency,
    Documents\Ecommerce\Money,
    Documents\Ecommerce\Option;


class EcommerceTest extends PHPUnit_Framework_TestCase
{

    protected $dm;

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

        $this->dm = DocumentManager::create(new Mongo(), $config);

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

        unset ($currency, $product);
    }

    public function tearDown()
    {
        $documents = array(
            'Documents\Ecommerce\ConfigurableProduct',
            'Documents\Ecommerce\StockItem',
            'Documents\Ecommerce\Currency',
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
    }

    public function testEmbedding()
    {
        $products = $this->dm->find('Documents\Ecommerce\ConfigurableProduct');
        $this->assertEquals(1, count($products));

        $products->valid() ?: $products->next();
        
        $product = $products->current();
        $this->assertEquals(3, count($product->getOptions()));
        $this->assertEquals(12.99, $product->getOption('small')->getPrice());

        $usdCurrency = $this->dm->findOne('Documents\Ecommerce\Currency', array('name' => 'USD'));
        $usdCurrency->setMultiplier('2');

        $this->assertTrue($product->getOption('small')->getStockItem() instanceof Documents\Ecommerce\StockItem);
        $this->assertNotNull($product->getOption('small')->getStockItem()->getId());
        $this->assertEquals(12.99 * 2, $product->getOption('small')->getPrice());
    }

    public function testMoneyDocumentsAvailableForReference()
    {
        $products = $this->dm->find('Documents\Ecommerce\ConfigurableProduct');
        $products->valid() ?: $products->next();

        $product = $products->current();
        $price =  $product->getOption('small')->getPrice(true);
        $currency = $price->getCurrency();
        $this->assertNotNull($currency->getId());
        $this->assertTrue($currency instanceof Currency);
        $this->assertEquals($currency, $this->dm->findOne('Documents\Ecommerce\Currency', array('name' => Currency::USD)));
    }

}