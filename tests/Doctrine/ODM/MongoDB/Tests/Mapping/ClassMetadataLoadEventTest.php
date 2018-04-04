<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ClassMetadataLoadEventTest extends BaseTest
{
    public function testEvent()
    {
        $metadataFactory = $this->dm->getMetadataFactory();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $this);
        $classMetadata = $metadataFactory->getMetadataFor(LoadEventTestDocument::class);
        $this->assertTrue($classMetadata->hasField('about'));
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $field = [
            'fieldName' => 'about',
            'type' => 'string',
        ];
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
