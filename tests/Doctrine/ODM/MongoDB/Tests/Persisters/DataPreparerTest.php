<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\Functional\Product;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Ecommerce\ConfigurableProduct;

class DataPreparerTest extends BaseTest
{

    private $dp;

    public function setUp()
    {
        parent::setUp();
        $this->dp = $this->dm->getUnitOfWork()->getDataPreparer();
    }

    public function tearDown()
    {
        unset($this->dp);
        parent::tearDown();
    }

    public function testPrepareInsertData()
    {
        $product = new ConfigurableProduct('Test Product');
        $this->dm->persist($product);
        $this->uow->computeChangeSets();
        $this->assertEquals(array(
            '_id'  => new \MongoId(),
            'name' => 'Test Product',
        ), $this->dp->prepareInsertData($product));
    }

}