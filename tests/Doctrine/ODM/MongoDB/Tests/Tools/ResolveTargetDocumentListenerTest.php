<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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

    public function setUp()
    {
        parent::setUp();

         $this->listener = new ResolveTargetDocumentListener();
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
