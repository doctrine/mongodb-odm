<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH245Test extends BaseTestCase
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

#[ODM\Document]
class GH245Order
{
    /** @var int|null */
    #[ODM\Id(strategy: 'NONE')]
    public $id;
}

#[ODM\Document]
class GH245OrderLog
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH245Order|null */
    #[ODM\ReferenceOne(targetDocument: GH245Order::class)]
    public $order;
}
