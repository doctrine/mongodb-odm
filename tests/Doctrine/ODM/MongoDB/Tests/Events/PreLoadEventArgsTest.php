<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Group;

class PreLoadEventArgsTest extends BaseTestCase
{
    public function testGetData(): void
    {
        $document = new Group('test');
        $dm       = $this->dm;
        $data     = ['id' => '1234', 'name' => 'test'];

        $eventArgs     = new PreLoadEventArgs($document, $dm, $data);
        $eventArgsData =& $eventArgs->getData();

        self::assertEquals('test', $eventArgsData['name']);

        $eventArgsData['name'] = 'alt name';

        self::assertEquals('alt name', $data['name']);
    }
}
