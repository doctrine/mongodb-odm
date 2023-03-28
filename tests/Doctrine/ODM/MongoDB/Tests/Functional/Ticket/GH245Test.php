<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH245Test extends BaseTest
{
    public function testTest(): void
    {
        $order     = new GH245Order();
        $order->id = 1;

        $orderLog        = new GH245OrderLog();
        $orderLog->order = $order;

        $this->dm->persist($orderLog);
        $this->dm->persist($order);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($order::class, $order->id);

        self::assertIsInt($order->id);

        $check = $this->dm->getDocumentCollection($orderLog::class)->findOne();
        self::assertIsInt($check['order']['$id']);
    }
}

/** @ODM\Document */
class GH245Order
{
    /**
     * @ODM\Id(strategy="NONE")
     *
     * @var int|null
     */
    public $id;
}

/** @ODM\Document */
class GH245OrderLog
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH245Order::class)
     *
     * @var GH245Order|null
     */
    public $order;
}
