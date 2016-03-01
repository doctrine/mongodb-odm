<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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
 * @ODM\Document
 */
class LoadEventTestDocument
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    private $about;
}
