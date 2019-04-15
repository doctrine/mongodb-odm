<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

class ResolveTargetDocumentListenerTest extends BaseTest
{
    /** @var DocumentManager */
    protected $dm;

    /** @var ResolveTargetDocumentListener */
    protected $listener;

    /** @var ClassMetadataFactory */
    private $factory;

    public function setUp()
    {
        parent::setUp();

        $this->listener = new ResolveTargetDocumentListener();
        $this->factory  = $this->dm->getMetadataFactory();
    }

    public function testResolveTargetDocumentListenerCanResolveTargetDocument()
    {
        $evm = $this->dm->getEventManager();

        $this->listener->addResolveTargetDocument(
            ResolveTargetInterface::class,
            ResolveTargetDocument::class,
            []
        );

        $this->listener->addResolveTargetDocument(
            TargetInterface::class,
            TargetDocument::class,
            []
        );

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);

        $cm   = $this->dm->getClassMetadata(ResolveTargetDocument::class);
        $meta = $cm->associationMappings;

        $this->assertSame(ResolveTargetDocument::class, $meta['refOne']['targetDocument']);
        $this->assertSame(TargetDocument::class, $meta['refMany']['targetDocument']);
        $this->assertSame(ResolveTargetDocument::class, $meta['embedOne']['targetDocument']);
        $this->assertSame(TargetDocument::class, $meta['embedMany']['targetDocument']);
    }

    public function testResolveTargetDocumentListenerCanRetrieveTargetDocumentByInterfaceName()
    {
        $this->listener->addResolveTargetDocument(ResolveTargetInterface::class, ResolveTargetDocument::class, []);

        $this->dm->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(ResolveTargetInterface::class);

        $this->assertSame($this->factory->getMetadataFor(ResolveTargetDocument::class), $cm);
    }

    public function testResolveTargetDocumentListenerCanRetrieveTargetDocumentByAbstractClassName()
    {
        $this->listener->addResolveTargetDocument(AbstractResolveTarget::class, ResolveTargetDocument::class, []);

        $this->dm->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(AbstractResolveTarget::class);

        $this->assertSame($this->factory->getMetadataFor(ResolveTargetDocument::class), $cm);
    }
}

interface ResolveTargetInterface
{
    public function getId();
}

interface TargetInterface extends ResolveTargetInterface
{
}

abstract class AbstractResolveTarget implements ResolveTargetInterface
{
}

/**
 * @ODM\Document
 */
class ResolveTargetDocument extends AbstractResolveTarget implements ResolveTargetInterface
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface::class) */
    private $refOne;

    /** @ODM\ReferenceMany(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface::class) */
    private $refMany;

    /** @ODM\EmbedOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface::class) */
    private $embedOne;

    /** @ODM\EmbedMany(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface::class) */
    private $embedMany;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ODM\Document
 */
class TargetDocument implements TargetInterface
{
    /** @ODM\Id */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
