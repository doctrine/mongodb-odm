<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH245Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
	public function testTest()
	{
		$order = new GH245Order();
		$order->id = 1;

		$orderLog = new GH245OrderLog();
		$orderLog->order = $order;

		$this->dm->persist($orderLog);
		$this->dm->persist($order);
		$this->dm->flush();
		$this->dm->clear();

		$user = $this->dm->find(get_class($order), $order->id);

		$this->assertTrue(is_int($order->id));

		$check = $this->dm->getDocumentCollection(get_class($orderLog))->findOne();
		$this->assertTrue(is_int($check['order']['$id']));
	}
}

/** @ODM\Document */
class GH245Order
{
	/** @ODM\Id(strategy="NONE") */
	public $id;
}

/** @ODM\Document */
class GH245OrderLog
{
	/** @ODM\Id */
	public $id;

	/** @ODM\ReferenceOne(targetDocument="GH245Order") */
	public $order;
}
