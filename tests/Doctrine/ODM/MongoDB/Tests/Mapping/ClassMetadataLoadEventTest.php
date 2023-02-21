<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;

class ClassMetadataLoadEventTest extends BaseTest
{
    public function testEvent(): void
    {
        $metadataFactory = $this->dm->getMetadataFactory();
        $evm             = $this->dm->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $this);
        $classMetadata = $metadataFactory->getMetadataFor(LoadEventTestDocument::class);
        self::assertTrue($classMetadata->hasField('about'));
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $field         = [
            'fieldName' => 'about',
            'type' => 'string',
        ];
        assert($classMetadata instanceof ClassMetadata);
        $classMetadata->mapField($field);
    }
}

/** @ODM\Document */
class LoadEventTestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /** @var mixed */
    private $about;
}
