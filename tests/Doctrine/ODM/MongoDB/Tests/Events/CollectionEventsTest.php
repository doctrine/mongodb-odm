<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

require_once __DIR__ . '/../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\CollectionEvents,
    Doctrine\ODM\MongoDB\Event\CollectionEventArgs;

class CollectionEventsTEst extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $_called = array();

    public function testTest()
    {
        $events = array(
            'preBatchInsert',
            'postBatchInsert',
            'preUpdate',
            'postUpdate',
            'preSaveFile',
            'postSaveFile',
            'preGetDBRef',
            'postGetDBRef',
            'preSave',
            'postSave',
            'preFind',
            'postFind',
            'preFindOne',
            'postFindOne'
        );
        foreach ($events as $key => $event) {
            $events[$key] = constant("\Doctrine\ODM\MongoDB\CollectionEvents::$event");
        }
        $this->dm->getEventManager()->addEventListener($events, $this);

        $insert = array(array(
            'username' => 'jwage'
        ));
        $collection = $this->dm->getDocumentCollection('Documents\User');
        $collection->batchInsert($insert);
        $collection->update(array(), array('username' => 'test'));
        $file = array('file' => 'file');
        $this->dm->getDocumentCollection('Documents\File')->saveFile($file);
        $cmd = $this->dm->getConfiguration()->getMongoCmd();
        $collection->getDbRef(array($this->escapeCommand('ref') => 'users', $this->escapeCommand('id') => 'theid'));
        $document = array('_id' => 'test', 'username' => 'jwage');
        $collection->save($document);
        $collection->find();
        $collection->findOne(array('username' => 'jwage'));
        $this->assertEquals($events, $this->_called);
    }

    public function __call($method, $args)
    {
        $this->_called[] = $method;
    }
}