<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Documents\Article;

class PreUpdateEventArgsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->dm->getEventManager()->addEventListener(Events::preUpdate, $this);
    }

    public function testChangeSetIsUpdated()
    {
        $a = new Article();
        $a->setTitle('Title');
        $this->dm->persist($a);
        $this->dm->flush();
        $a->setBody('Body');
        $this->dm->flush();
        $this->dm->clear();
        $a = $this->dm->find(Article::class, $a->getId());
        $this->assertEquals('Changed', $a->getBody());
    }

    public function preUpdate(PreUpdateEventArgs $e)
    {
        $e->setNewValue('body', 'Changed');
    }
}
