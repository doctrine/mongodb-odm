<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Events;

class ClassMetadataLoadEventTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEvent()
    {
        $metadataFactory = $this->dm->getMetadataFactory();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $this);
        $classMetadata = $metadataFactory->getMetadataFor('Doctrine\ODM\MongoDB\Tests\Mapping\LoadEventTestDocument');
        $this->assertTrue($classMetadata->hasField('about'));
    }

    public function loadClassMetadata(\Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $field = array(
            'fieldName' => 'about',
            'type' => 'string'
        );
        $classMetadata->mapField($field);
    }
}

/**
 * @Document
 */
class LoadEventTestDocument
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    private $about;
}