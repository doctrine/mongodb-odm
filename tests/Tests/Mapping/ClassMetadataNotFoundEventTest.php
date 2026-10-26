<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\StringType;
use Doctrine\ODM\MongoDB\Types\TypeProvider;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;

class ClassMetadataNotFoundEventTest extends BaseTestCase
{
    public function testMetadataFromEventReceivesTheConfiguredTypeProvider(): void
    {
        $typeProvider = $this->dm->getConfiguration()->getTypeProvider();
        self::assertInstanceOf(TypeRegistry::class, $typeProvider);
        $typeProvider->register('custom_from_config', StringType::class);

        $listener = new class {
            public TypeProvider|null $typeProviderFromEvent = null;

            public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $eventArgs): void
            {
                $this->typeProviderFromEvent = $eventArgs->getTypeProvider();
                $eventArgs->setFoundMetadata(new ClassMetadata(ClassMetadataNotFoundDocument::class));
            }
        };

        $this->dm->getEventManager()->addEventListener(Events::onClassMetadataNotFound, $listener);

        $metadata = $this->dm->getClassMetadata(ClassMetadataNotFoundDocument::class);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'custom_from_config']);

        self::assertSame($typeProvider, $listener->typeProviderFromEvent);
        self::assertSame('custom_from_config', $metadata->getFieldMapping('name')['type']);
    }

    public function testMetadataFromEventKeepsTheTypeProviderSetByTheListener(): void
    {
        $listenerProvider = new TypeRegistry(['custom_from_listener' => StringType::class]);

        $this->dm->getEventManager()->addEventListener(
            Events::onClassMetadataNotFound,
            new class ($listenerProvider) {
                public function __construct(private TypeRegistry $typeProvider)
                {
                }

                public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $eventArgs): void
                {
                    $metadata = new ClassMetadata(ClassMetadataNotFoundDocument::class);
                    $metadata->setTypeProvider($this->typeProvider);
                    $eventArgs->setFoundMetadata($metadata);
                }
            },
        );

        $metadata = $this->dm->getClassMetadata(ClassMetadataNotFoundDocument::class);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'custom_from_listener']);

        self::assertSame('custom_from_listener', $metadata->getFieldMapping('name')['type']);
    }
}

class ClassMetadataNotFoundDocument
{
    /** @var mixed */
    public $name;
}
