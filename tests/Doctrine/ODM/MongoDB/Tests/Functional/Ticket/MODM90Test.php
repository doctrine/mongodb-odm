<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class MODM90Test extends BaseTest
{
    private MODM90EventListener $listener;

    private function getDocumentManager(): ?DocumentManager
    {
        $this->listener = new MODM90EventListener();
        $evm            = $this->dm->getEventManager();
        $events         = [
            Events::preUpdate,
            Events::postUpdate,
        ];
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdatedOnFlush(): void
    {
        $dm = $this->getDocumentManager();

        $testDoc                 = new MODM90TestDocument();
        $testDoc->name           = 'Parent';
        $testDoc->embedded       = new MODM90TestEmbeddedDocument();
        $testDoc->embedded->name = 'Child';
        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(MODM90TestDocument::class, $testDoc->id);

        // run a flush, in theory, nothing should be flushed.
        $dm->flush();
        $dm->clear();

        // no update events should be called
        $called = [];
        self::assertEquals($called, $this->listener->called);
    }

    /**
     * Ensures that the descriminator field is not unset if it's a
     * real property on the document.
     */
    public function testDiscriminatorFieldValuePresentIfRealProperty(): void
    {
        $dm = $this->getDocumentManager();

        $testDoc                 = new MODM90TestDocument();
        $testDoc->name           = 'Parent';
        $testDoc->embedded       = new MODM90Test2EmbeddedDocument();
        $testDoc->embedded->name = 'Child';
        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(MODM90TestDocument::class, $testDoc->id);

        self::assertInstanceOf(MODM90Test2EmbeddedDocument::class, $testDoc->embedded);
        self::assertEquals('test2', $testDoc->embedded->type);
    }
}

class MODM90EventListener
{
    /** @var array<string, class-string[]> */
    public $called = [];

    /** @param array{LifecycleEventArgs} $args */
    public function __call(string $method, array $args): void
    {
        $document                = $args[0]->getDocument();
        $className               = get_class($document);
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM90TestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne
     * (
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "test"=MODM90TestEmbeddedDocument::class,
     *     "test2"=MODM90Test2EmbeddedDocument::class
     *   }
     *  )
     *
     * @var MODM90TestEmbeddedDocument|MODM90Test2EmbeddedDocument|null
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM90TestEmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\EmbeddedDocument */
class MODM90Test2EmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="string") The discriminator field is a real property
     *
     * @var string
     */
    public $type;
}
