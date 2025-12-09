<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM81Test extends BaseTestCase
{
    private function getDocumentManager(): DocumentManager
    {
        return $this->dm;
    }

    public function testDocumentIdWithSameProxyId(): void
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

        self::assertSame($doc1, $doc1->getEmbeddedDocuments()->get(0)->getRefTodocument1());

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->find(MODM81TestDocument::class, $doc1->getId());
        self::assertNotNull($doc1);
        self::assertEquals('Document1Change', $doc1->getName());

        $doc1->getEmbeddedDocuments()->get(0)->getRefTodocument1()->setName('Document1ProxyChange');

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->find(MODM81TestDocument::class, $doc1->getId());
        self::assertEquals('Document1ProxyChange', $doc1->getName());
    }
}

#[ODM\Document]
class MODM81TestDocument
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $name;

    /** @var Collection<int, MODM81TestEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: MODM81TestEmbeddedDocument::class)]
    protected $embeddedDocuments;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @return Collection<int, MODM81TestEmbeddedDocument> */
    public function getEmbeddedDocuments(): Collection
    {
        return $this->embeddedDocuments;
    }

    /** @param MODM81TestEmbeddedDocument[] $documents */
    public function setEmbeddedDocuments(array $documents): void
    {
        $this->embeddedDocuments = new ArrayCollection($documents);
    }
}

#[ODM\EmbeddedDocument]
class MODM81TestEmbeddedDocument
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $message;

    /** @var MODM81TestDocument */
    #[ODM\ReferenceOne(targetDocument: MODM81TestDocument::class)]
    public $refTodocument1;

    /** @var MODM81TestDocument */
    #[ODM\ReferenceOne(targetDocument: MODM81TestDocument::class)]
    public $refTodocument2;

    public function __construct(MODM81TestDocument $document1, MODM81TestDocument $document2, string $message)
    {
        $this->refTodocument1 = $document1;
        $this->refTodocument2 = $document2;
        $this->message        = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRefTodocument1(): MODM81TestDocument
    {
        return $this->refTodocument1;
    }

    public function getRefTodocument2(): MODM81TestDocument
    {
        return $this->refTodocument2;
    }
}
