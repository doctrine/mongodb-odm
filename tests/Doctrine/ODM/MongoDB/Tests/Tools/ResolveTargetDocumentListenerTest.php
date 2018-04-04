<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

class ResolveTargetDocumentListenerTest extends BaseTest
{
    /** @var DocumentManager */
    protected $dm;

    /** @var ResolveTargetDocumentListener */
    protected $listener;

    public function setUp()
    {
        parent::setUp();

         $this->listener = new ResolveTargetDocumentListener();
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

        $cm = $this->dm->getClassMetadata(ResolveTargetDocument::class);
        $meta = $cm->associationMappings;

        $this->assertSame(ResolveTargetDocument::class, $meta['refOne']['targetDocument']);
        $this->assertSame(TargetDocument::class, $meta['refMany']['targetDocument']);
        $this->assertSame(ResolveTargetDocument::class, $meta['embedOne']['targetDocument']);
        $this->assertSame(TargetDocument::class, $meta['embedMany']['targetDocument']);
    }
}

interface ResolveTargetInterface
{
    public function getId();
}

interface TargetInterface extends ResolveTargetInterface
{
}

/**
 * @ODM\Document
 */
class ResolveTargetDocument implements ResolveTargetInterface
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface") */
    private $refOne;

    /** @ODM\ReferenceMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface") */
    private $refMany;

    /** @ODM\EmbedOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface") */
    private $embedOne;

    /** @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface") */
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
