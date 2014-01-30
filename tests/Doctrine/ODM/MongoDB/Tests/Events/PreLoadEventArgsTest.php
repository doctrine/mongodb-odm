<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Documents\Group;

class PreLoadEventArgsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetData()
    {
        $document = new Group('test');
        $dm = $this->dm;
        $data = ['id' => '1234', 'name' => 'test'];

        $eventArgs = new PreLoadEventArgs($document, $dm, $data);

        $this->assertEquals('test', $eventArgs->getData()['name']);

        $eventArgs->getData()['name'] = 'alt name';

        $this->assertEquals('alt name', $data['name']);
    }
}
