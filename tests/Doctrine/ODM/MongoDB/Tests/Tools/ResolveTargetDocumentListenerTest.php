<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

class ResolveTargetDocumentListenerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $dm;

    /**
     * @var ResolveTargetDocumentListener
     */
    protected $listener;

    /**
     * @var ClassMetadataFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();

         $this->listener = new ResolveTargetDocumentListener();
        $this->factory = $this->dm->getMetadataFactory();
    }

    public function testResolveTargetDocumentListenerCanResolveTargetDocument()
    {
        $evm = $this->dm->getEventManager();

        $this->listener->addResolveTargetDocument(
            'Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface',
            'Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetDocument',
            array()
        );

        $this->listener->addResolveTargetDocument(
            'Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface',
            'Doctrine\ODM\MongoDB\Tests\Tools\TargetDocument',
            array()
        );

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);

        $cm = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetDocument');
        $meta = $cm->associationMappings;

        $this->assertSame('Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetDocument', $meta['refOne']['targetDocument']);
        $this->assertSame('Doctrine\ODM\MongoDB\Tests\Tools\TargetDocument', $meta['refMany']['targetDocument']);
        $this->assertSame('Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetDocument', $meta['embedOne']['targetDocument']);
        $this->assertSame('Doctrine\ODM\MongoDB\Tests\Tools\TargetDocument', $meta['embedMany']['targetDocument']);
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
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface")
     */
    private $refOne;

    /**
     * @ODM\ReferenceMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface")
     */
    private $refMany;

    /**
     * @ODM\EmbedOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\ResolveTargetInterface")
     */
    private $embedOne;

    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Tools\TargetInterface")
     */
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
    /**
     * @ODM\Id
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
