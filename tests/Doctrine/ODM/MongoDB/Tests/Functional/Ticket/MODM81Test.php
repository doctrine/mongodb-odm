<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\ODMEvents,
 Doctrine\Common\Collections\ArrayCollection;

class MODM81Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest {

    /**
     * @return DocumentManager
     */
    private function getDocumentManager() {
        return $this->dm;
    }

    public function testDocumentIdWithSameProxyId() {
        $dm = $this->getDocumentManager();

        $doc1 = new MODM81TestDocument();
        $doc1->setName('Document1');

        $doc2 = new MODM81TestDocument();
        $doc2->setName('Document2');

        $dm->persist($doc1);
        $dm->persist($doc2);
        $dm->flush();
        $dm->refresh($doc1);
        $dm->refresh($doc2);

        $embedded = new MODM81TestEmbeddedDocument($doc1, $doc2, 'Test1');
        $doc1->setEmbeddedDocuments(array($embedded));
        $doc2->setEmbeddedDocuments(array($embedded));

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->findOne(__NAMESPACE__ . '\MODM81TestDocument', array('_id' => new \MongoId($doc1->getId())));
        $doc1->setName('Document1Change');

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->findOne(__NAMESPACE__ . '\MODM81TestDocument', array('_id' => new \MongoId($doc1->getId())));
        $this->assertEquals('Document1Change', $doc1->getName());

        $doc1->getEmbeddedDocuments()->get(0)->getRefTodocument1()->setName('Document1ProxyChange');

        $dm->flush();
        $dm->clear();

        $doc1 = $dm->findOne(__NAMESPACE__ . '\MODM81TestDocument', array('_id' => new \MongoId($doc1->getId())));
        $this->assertEquals('Document1ProxyChange', $doc1->getName());
    }

}

/** @Document */
class MODM81TestDocument {

    /** @Id */
    protected $id;
    /** @String */
    protected $name;
    /** @EmbedMany(targetDocument="MODM81TestEmbeddedDocument") */
    protected $embeddedDocuments;

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEmbeddedDocuments() {
        return $this->embeddedDocuments;
    }

    /**
     * @param array $documents
     */
    public function setEmbeddedDocuments($documents) {
        $this->embeddedDocuments = new ArrayCollection($documents);
    }

}

/** @EmbeddedDocument */
class MODM81TestEmbeddedDocument {

    /** @String */
    public $message;
    /** @ReferenceOne(targetDocument="MODM81TestDocument") */
    public $refTodocument1;
    /** @ReferenceOne(targetDocument="MODM81TestDocument") */
    public $refTodocument2;

    public function __construct($document1, $document2, $message) {
        $this->refTodocument1 = $document1;
        $this->refTodocument2 = $document2;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return MODM81TestDocument
     */
    public function getRefTodocument1() {
        return $this->refTodocument1;
    }

    /**
     * @return MODM81TestDocument
     */
    public function getRefTodocument2() {
        return $this->refTodocument2;
    }

}