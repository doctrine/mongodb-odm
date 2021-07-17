<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectIdInterface;
use function phpversion;
use function version_compare;

class GH2339Test extends BaseTest
{
    public function setUp(): void
    {
        if (version_compare((string) phpversion(), '8.0', '<')) {
            self::markTestSkipped('PHP 8.0 is required to run this test');
        }

        parent::setUp();
    }

    public function testObjectIdInterfaceInEmbeddedDocuments()
    {
        $parent = new ParentDocument();
        $embedded = new EmbeddedDocument();

        $parent->addEmbedded($embedded);

        $this->dm->persist($parent);
        $this->dm->flush();

        $document = $this->dm->find(ParentDocument::class, $parent->getId());

        $this->assertEquals($parent->getId(), $document->getId());
        $this->assertNotEmpty($document->getEmbedded());
        $this->assertInstanceOf(EmbeddedDocument::class, $document->getEmbedded()[0]);
        $this->assertEquals($embedded->getId(), $document->getEmbedded()[0]->getId());
    }
}


/**
 * @ODM\Document
 */
class ParentDocument
{
    /**
     * @ODM\Id
     */
    protected ObjectIdInterface $id;

    /**
     * @var EmbeddedDocument[]
     *
     * @ODM\EmbedMany(targetDocument=EmbeddedDocument::class)
     */
    protected array $embedded = [];

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }

    public function addEmbedded(EmbeddedDocument $document)
    {
        $this->embedded[] = $document;
    }

    public function getEmbedded(): array
    {
        return $this->getEmbedded();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocument
{
    /**
     * @ODM\Id
     */
    protected ObjectIdInterface $id;

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }
}
