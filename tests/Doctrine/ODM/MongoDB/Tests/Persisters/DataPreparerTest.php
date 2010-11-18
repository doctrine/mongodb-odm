<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

use Documents\Ecommerce\Currency;
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

    /**
     * @dataProvider getDocumentsAndExpectedData
     */
    public function testPrepareInsertData($document, array $expectedData)
    {
        $this->dm->persist($document);
        $this->uow->computeChangeSets();
        $this->assertEquals($expectedData, $this->dp->prepareInsertData($document));
    }

    /**
     * Provides data for @see DataPreparerTest::testPrepareInsertData()
     * Returns arrays of array(document => expected data)
     *
     * @return array
     */
    public function getDocumentsAndExpectedData()
    {
        return array(
            array(new ConfigurableProduct('Test Product'), array('_id'  => new \MongoId(), 'name' => 'Test Product')),
            array(new Currency('USD', 1), array('_id' => new \MongoId(), 'name' => 'USD', 'multiplier' => 1)),
        );
    }

}