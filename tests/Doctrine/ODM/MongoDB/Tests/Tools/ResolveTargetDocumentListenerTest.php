<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

class ResolveTargetDocumentListenerTest extends BaseTestCase
{
    protected ResolveTargetDocumentListener $listener;

    private ClassMetadataFactoryInterface $factory;

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

#[ODM\Document]
class ResolveTargetDocument extends AbstractResolveTarget implements ResolveTargetInterface
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var ResolveTargetInterface|null */
    #[ODM\ReferenceOne(targetDocument: ResolveTargetInterface::class)]
    private $refOne;

    /** @var Collection<int, TargetInterface> */
    #[ODM\ReferenceMany(targetDocument: TargetInterface::class)]
    private $refMany;

    /** @var ResolveTargetInterface|null */
    #[ODM\EmbedOne(targetDocument: ResolveTargetInterface::class)]
    private $embedOne;

    /** @var Collection<int, TargetInterface> */
    #[ODM\EmbedMany(targetDocument: TargetInterface::class)]
    private $embedMany;

    public function getId(): ?string
    {
        return $this->id;
    }
}

#[ODM\Document]
class TargetDocument implements TargetInterface
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    public function getId(): ?string
    {
        return $this->id;
    }
}
