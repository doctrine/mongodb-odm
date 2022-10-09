<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use stdClass;

class OnClassMetadataNotFoundEventArgsTest extends TestCase
{
    public function testEventArgsMutability(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);

        $args = new OnClassMetadataNotFoundEventArgs(stdClass::class, $documentManager);

        self::assertSame(stdClass::class, $args->getClassName());
        self::assertSame($documentManager, $args->getObjectManager());

        self::assertNull($args->getFoundMetadata());

        $metadata = $this->createMock(ClassMetadata::class);

        $args->setFoundMetadata($metadata);

        self::assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        self::assertNull($args->getFoundMetadata());
    }
}
