<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function assert;

class ClassMetadataLoadEventTest extends BaseTestCase
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

#[ODM\Document]
class LoadEventTestDocument
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $name;

    /** @var mixed */
    private $about;
}
