<?php
namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;
use Documents\Ecommerce\StockItem;

/**
 * Test for UnitOfWork::detach()
 */
class GH978Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDetach()
    {
        $document = new ConfigurableProduct('foo bar');
        //option 1
        $document->addOption(
            new Option(
                'foo option',
                new Money(
                    10.0,
                    new Currency('EURO')
                ),
                new StockItem('foo item')
            )
        );
        //option 2
        $document->addOption(
            new Option(
                'bar option',
                new Money(
                    20.0,
                    new Currency('EURO')
                ),
                new StockItem('bar item')
            )
        );
        //persist document
        $this->dm->persist($document);
        //check the count of uow (Persist operation of money is cascaded)
        $this->assertEquals(9, $this->uow->size());
        //detach the document
        $this->dm->detach($document);
        //should be 0 now
        $this->assertEquals(0, $this->uow->size());
    }
}
