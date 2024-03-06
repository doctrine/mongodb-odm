<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM91Test extends BaseTestCase
{
    private MODM91EventListener $listener;

    private function getDocumentManager(): ?DocumentManager
    {
        $this->listener = new MODM91EventListener();
        $evm            = $this->dm->getEventManager();
        $events         = [
            Events::preUpdate,
            Events::postUpdate,
        ];
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdated(): void
    {
        $dm = $this->getDocumentManager();

        $testDoc           = new MODM91TestDocument();
        $testDoc->name     = 'test doc';
        $testDoc->embedded = new MODM91TestEmbeddedDocument();

        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(MODM91TestDocument::class, $testDoc->id);
        $dm->flush();
        $dm->clear();

        $called = [];
        self::assertEquals($called, $this->listener->called);
    }
}

class MODM91EventListener
{
    /** @var array<string, class-string[]>  */
    public $called = [];

    /** @param array{LifecycleEventArgs} $args */
    public function __call(string $method, array $args): void
    {
        $document                = $args[0]->getDocument();
        $className               = $document::class;
        $this->called[$method][] = $className;
    }
}

#[ODM\Document]
class MODM91TestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var MODM91TestEmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: MODM91TestEmbeddedDocument::class)]
    public $embedded;
}

#[ODM\EmbeddedDocument]
class MODM91TestEmbeddedDocument
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
