<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

class OnClassMetadataNotFoundEventArgsTest extends TestCase
{
    public function testEventArgsMutability()
    {
        $documentManager = $this->createMock(DocumentManager::class);

        $args = new OnClassMetadataNotFoundEventArgs('foo', $documentManager);

        $this->assertSame('foo', $args->getClassName());
        $this->assertSame($documentManager, $args->getObjectManager());

        $this->assertNull($args->getFoundMetadata());

        $metadata = $this->createMock(ClassMetadata::class);

        $args->setFoundMetadata($metadata);

        $this->assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        $this->assertNull($args->getFoundMetadata());
    }
}
