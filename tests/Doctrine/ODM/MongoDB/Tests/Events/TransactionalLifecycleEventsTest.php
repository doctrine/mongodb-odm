<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\Client;
use MongoDB\Driver\Session;
use PHPUnit\Framework\Assert;

class TransactionalLifecycleEventsTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipTestIfTransactionalFlushDisabled();
    }

    public function tearDown(): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => 'off',
        ]);

        parent::tearDown();
    }

    public function testPersistEvents(): void
    {
        $root       = new RootEventDocument();
        $root->name = 'root';

        $root->embedded       = new EmbeddedEventDocument();
        $root->embedded->name = 'embedded';

        $this->createFailPoint('insert');

        $this->dm->persist($root);
        $this->dm->flush();

        $this->assertSame(1, $root->postPersist);
        $this->assertSame(1, $root->embedded->postPersist);
    }

    public function testUpdateEvents(): void
    {
        $root       = new RootEventDocument();
        $root->name = 'root';

        $root->embedded       = new EmbeddedEventDocument();
        $root->embedded->name = 'embedded';

        $this->dm->persist($root);
        $this->dm->flush();

        $this->createFailPoint('update');

        $root->name           = 'updated';
        $root->embedded->name = 'updated';

        $this->dm->flush();

        $this->assertSame(1, $root->preUpdate);
        $this->assertSame(1, $root->postUpdate);
        $this->assertSame(1, $root->embedded->preUpdate);
        $this->assertSame(1, $root->embedded->postUpdate);
    }

    public function testUpdateEventsRootOnly(): void
    {
        $root       = new RootEventDocument();
        $root->name = 'root';

        $root->embedded       = new EmbeddedEventDocument();
        $root->embedded->name = 'embedded';

        $this->dm->persist($root);
        $this->dm->flush();

        $this->createFailPoint('update');

        $root->name = 'updated';

        $this->dm->flush();

        $this->assertSame(1, $root->preUpdate);
        $this->assertSame(1, $root->postUpdate);
        $this->assertSame(0, $root->embedded->preUpdate);
        $this->assertSame(0, $root->embedded->postUpdate);
    }

    public function testUpdateEventsEmbeddedOnly(): void
    {
        $root       = new RootEventDocument();
        $root->name = 'root';

        $root->embedded       = new EmbeddedEventDocument();
        $root->embedded->name = 'embedded';

        $this->dm->persist($root);
        $this->dm->flush();

        $this->createFailPoint('update');

        $root->embedded->name = 'updated';

        $this->dm->flush();

        $this->assertSame(1, $root->preUpdate);
        $this->assertSame(1, $root->postUpdate);

        $this->assertSame(1, $root->embedded->preUpdate);
        $this->assertSame(1, $root->embedded->postUpdate);
    }

    public function testUpdateEventsWithNewEmbeddedDocument(): void
    {
        $firstEmbedded       = new EmbeddedEventDocument();
        $firstEmbedded->name = 'embedded';

        $secondEmbedded       = new EmbeddedEventDocument();
        $secondEmbedded->name = 'new';

        $root           = new RootEventDocument();
        $root->name     = 'root';
        $root->embedded = $firstEmbedded;

        $this->dm->persist($root);
        $this->dm->flush();

        $this->createFailPoint('update');

        $root->name     = 'updated';
        $root->embedded = $secondEmbedded;

        $this->dm->flush();

        $this->assertSame(1, $root->preUpdate);
        $this->assertSame(1, $root->postUpdate);

        // First embedded document was removed but not updated
        $this->assertSame(1, $firstEmbedded->postRemove);
        $this->assertSame(0, $firstEmbedded->preUpdate);
        $this->assertSame(0, $firstEmbedded->postUpdate);

        // Second embedded document was persisted but not updated
        $this->assertSame(1, $secondEmbedded->postPersist);
        $this->assertSame(0, $secondEmbedded->preUpdate);
        $this->assertSame(0, $secondEmbedded->postUpdate);
    }

    public function testRemoveEvents(): void
    {
        $root       = new RootEventDocument();
        $root->name = 'root';

        $root->embedded       = new EmbeddedEventDocument();
        $root->embedded->name = 'embedded';

        $this->dm->persist($root);
        $this->dm->flush();

        $this->createFailPoint('delete');

        $this->dm->remove($root);
        $this->dm->flush();

        $this->assertSame(1, $root->postRemove);
        $this->assertSame(1, $root->embedded->postRemove);
    }

    /** Create a document manager with a single host to ensure failpoints target the correct server */
    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $client = new Client(self::getUri(false), [], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        return DocumentManager::create($client, $config);
    }

    private function createFailPoint(string $failCommand): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 192, // FailPointEnabled
                'errorLabels' => ['TransientTransactionError'],
                'failCommands' => [$failCommand],
            ],
        ]);
    }
}

#[ODM\MappedSuperclass]
#[ODM\HasLifecycleCallbacks]
abstract class BaseEventDocument
{
    public function __construct()
    {
    }

    #[ODM\Field(type: Type::STRING)]
    public ?string $name = null;

    public int $preUpdate = 0;

    public int $postPersist = 0;

    public int $postUpdate = 0;

    public int $postRemove = 0;

    /** @ODM\PreUpdate */
    #[ODM\PreUpdate]
    public function preUpdate(Event\PreUpdateEventArgs $e): void
    {
        $this->assertTransactionState($e);
        $this->preUpdate++;
    }

    #[ODM\PostPersist]
    public function postPersist(Event\LifecycleEventArgs $e): void
    {
        $this->assertTransactionState($e);
        $this->postPersist++;
    }

    #[ODM\PostUpdate]
    public function postUpdate(Event\LifecycleEventArgs $e): void
    {
        $this->assertTransactionState($e);
        $this->postUpdate++;
    }

    #[ODM\PostRemove]
    public function postRemove(Event\LifecycleEventArgs $e): void
    {
        $this->assertTransactionState($e);
        $this->postRemove++;
    }

    private function assertTransactionState(LifecycleEventArgs $e): void
    {
        Assert::assertTrue($e->isInTransaction());
        Assert::assertInstanceOf(Session::class, $e->session);
    }
}

#[ODM\EmbeddedDocument]
class EmbeddedEventDocument extends BaseEventDocument
{
}

#[ODM\Document]
class RootEventDocument extends BaseEventDocument
{
    #[ODM\Id]
    public string $id;

    #[ODM\EmbedOne(targetDocument: EmbeddedEventDocument::class)]
    public ?EmbeddedEventDocument $embedded;
}
