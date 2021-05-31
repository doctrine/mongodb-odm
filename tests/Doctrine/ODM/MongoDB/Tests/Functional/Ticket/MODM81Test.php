<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM81Test extends BaseTest
{
    private function getDocumentManager(): DocumentManager
    {
        return $this->dm;
    }

    public function testDocumentIdWithSameProxyId()
    {
        $dm = $this->getDocumentManager();

        $doc1 = new MODM81TestDocument();
        $doc1->setName('Document1');

        $doc2 = new MODM81TestDocument();
        $doc2->setName('Document2');

        $dm->persist($doc1);
        $dm->persist($doc2);
        $dm->flush();

        $embedded = new MODM81TestEmbeddedDocument($doc1, $doc2, 'Test1');
        $doc1->setEmbeddedDocuments([$embedded]);
        $doc2->setEmbeddedDocuments([$embedded]);

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->find(MODM81TestDocument::class, $doc1->getId());
        $doc1->setName('Document1Change');

        $this->assertSame($doc1, $doc1->getEmbeddedDocuments()->get(0)->getRefTodocument1());

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->find(MODM81TestDocument::class, $doc1->getId());
        $this->assertNotNull($doc1);
        $this->assertEquals('Document1Change', $doc1->getName());

        $doc1->getEmbeddedDocuments()->get(0)->getRefTodocument1()->setName('Document1ProxyChange');

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->find(MODM81TestDocument::class, $doc1->getId());
        $this->assertEquals('Document1ProxyChange', $doc1->getName());
    }
}

/** @ODM\Document */
class MODM81TestDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\EmbedMany(targetDocument=MODM81TestEmbeddedDocument::class) */
    protected $embeddedDocuments;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return ArrayCollection
     */
    public function getEmbeddedDocuments()
    {
        return $this->embeddedDocuments;
    }

    /**
     * @param array $documents
     */
    public function setEmbeddedDocuments($documents)
    {
        $this->embeddedDocuments = new ArrayCollection($documents);
    }
}

/** @ODM\EmbeddedDocument */
class MODM81TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $message;

    /** @ODM\ReferenceOne(targetDocument=MODM81TestDocument::class) */
    public $refTodocument1;

    /** @ODM\ReferenceOne(targetDocument=MODM81TestDocument::class) */
    public $refTodocument2;

    public function __construct($document1, $document2, $message)
    {
        $this->refTodocument1 = $document1;
        $this->refTodocument2 = $document2;
        $this->message        = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return MODM81TestDocument
     */
    public function getRefTodocument1()
    {
        return $this->refTodocument1;
    }

    /**
     * @return MODM81TestDocument
     */
    public function getRefTodocument2()
    {
        return $this->refTodocument2;
    }
}
