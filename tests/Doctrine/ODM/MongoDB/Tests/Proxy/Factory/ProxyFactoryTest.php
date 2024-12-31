<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Proxy\Factory;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Cart;
use Documents\DocumentWithUnmappedProperties;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use PHPUnit\Framework\MockObject\MockObject;
use ProxyManager\Proxy\GhostObjectInterface;

class ProxyFactoryTest extends BaseTestCase
{
    /** @var Client|MockObject */
    private Client $client;

    public function testProxyInitializeWithException(): void
    {
        $this->dm = $this->createMockedDocumentManager();

        $collection = $this->createMock(Collection::class);
        $database   = $this->createMock(Database::class);

        $this->client->expects($this->once())
            ->method('selectDatabase')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->willReturn($collection);

        $collection->expects($this->once())
            ->method('findOne')
            ->willThrowException(LockException::lockFailed(null));

        $uow = $this->dm->getUnitOfWork();

        $proxy = $this->dm->getReference(Cart::class, '123');
        self::assertTrue(self::isLazyObject($proxy));

        $closure = static function (DocumentNotFoundEventArgs $eventArgs) {
            self::fail('DocumentNotFoundListener should not be called');
        };
        $this->dm->getEventManager()->addEventListener(Events::documentNotFound, new DocumentNotFoundListener($closure));

        try {
            $this->uow->initializeObject($proxy);
            self::fail('An exception should have been thrown');
        } catch (LockException $exception) {
            self::assertInstanceOf(LockException::class, $exception);
        }

        $uow->computeChangeSets();

        self::assertTrue($this->uow->isUninitializedObject($proxy), 'Proxy should not be initialized');
    }

    public function tearDown(): void
    {
        // db connection is mocked, nothing to clean up
    }

    private function createMockedDocumentManager(): DocumentManager
    {
        $config = $this->getConfiguration();

        $this->client = $this->createMock(Client::class);

        return DocumentManager::create($this->client, $config);
    }

    public function testCreateProxyForDocumentWithUnmappedProperties(): void
    {
        $proxy = $this->dm->getReference(DocumentWithUnmappedProperties::class, '123');
        self::assertTrue(self::isLazyObject($proxy));

        // Disable initializer so we can access properties without initialising the object
        if ($proxy instanceof InternalProxy) {
            $proxy->__setInitialized(true);
        } elseif ($proxy instanceof GhostObjectInterface) {
            $proxy->setProxyInitializer(null);
        }

        self::assertSame('bar', $proxy->foo);
    }
}

class DocumentNotFoundListener
{
    public function __construct(private Closure $closure)
    {
    }

    public function documentNotFound(DocumentNotFoundEventArgs $eventArgs): void
    {
        $closure = $this->closure;
        $closure($eventArgs);
    }
}
