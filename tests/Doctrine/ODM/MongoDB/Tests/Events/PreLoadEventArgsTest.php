<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Group;

class PreLoadEventArgsTest extends BaseTest
{
    public function testGetData()
    {
        $document = new Group('test');
        $dm = $this->dm;
        $data = ['id' => '1234', 'name' => 'test'];

        $eventArgs = new PreLoadEventArgs($document, $dm, $data);
        $eventArgsData =& $eventArgs->getData();

        $this->assertEquals('test', $eventArgsData['name']);

        $eventArgsData['name'] = 'alt name';

        $this->assertEquals('alt name', $data['name']);
    }
}
