<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

class ResolveTargetDocumentListenerTest extends BaseTest
{
    protected ResolveTargetDocumentListener $listener;

    private ClassMetadataFactory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new ResolveTargetDocumentListener();
        $this->factory  = $this->dm->getMetadataFactory();
    }

    public function testResolveTargetDocumentListenerCanResolveTargetDocument(): void
    {
        $evm = $this->dm->getEventManager();

        $this->listener->addResolveTargetDocument(
            ResolveTargetInterface::class,
            ResolveTargetDocument::class,
            [],
        );

        $this->listener->addResolveTargetDocument(
            TargetInterface::class,
            TargetDocument::class,
            [],
        );

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);

        $cm   = $this->dm->getClassMetadata(ResolveTargetDocument::class);
        $meta = $cm->associationMappings;

        self::assertSame(ResolveTargetDocument::class, $meta['refOne']['targetDocument']);
        self::assertSame(TargetDocument::class, $meta['refMany']['targetDocument']);
        self::assertSame(ResolveTargetDocument::class, $meta['embedOne']['targetDocument']);
        self::assertSame(TargetDocument::class, $meta['embedMany']['targetDocument']);
    }

    public function testResolveTargetDocumentListenerCanRetrieveTargetDocumentByInterfaceName(): void
    {
        $this->listener->addResolveTargetDocument(ResolveTargetInterface::class, ResolveTargetDocument::class, []);

        $this->dm->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(ResolveTargetInterface::class);

        self::assertSame($this->factory->getMetadataFor(ResolveTargetDocument::class), $cm);
    }

    public function testResolveTargetDocumentListenerCanRetrieveTargetDocumentByAbstractClassName(): void
    {
        $this->listener->addResolveTargetDocument(AbstractResolveTarget::class, ResolveTargetDocument::class, []);

        $this->dm->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(AbstractResolveTarget::class);

        self::assertSame($this->factory->getMetadataFor(ResolveTargetDocument::class), $cm);
    }
}

interface ResolveTargetInterface
{
    /** @return mixed */
    public function getId();
}

interface TargetInterface extends ResolveTargetInterface
{
}

abstract class AbstractResolveTarget implements ResolveTargetInterface
{
}

/** @ODM\Document */
class ResolveTargetDocument extends AbstractResolveTarget implements ResolveTargetInterface
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface::class)
     *
     * @var ResolveTargetInterface|null
     */
    private $refOne;

    /**
     * @ODM\ReferenceMany(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface::class)
     *
     * @var Collection<int, TargetInterface>
     */
    private $refMany;

    /**
     * @ODM\EmbedOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface::class)
     *
     * @var ResolveTargetInterface|null
     */
    private $embedOne;

    /**
     * @ODM\EmbedMany(targetDocument=Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface::class)
     *
     * @var Collection<int, TargetInterface>
     */
    private $embedMany;

    public function getId(): ?string
    {
        return $this->id;
    }
}

/** @ODM\Document */
class TargetDocument implements TargetInterface
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    public function getId(): ?string
    {
        return $this->id;
    }
}
