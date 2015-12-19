<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;

class GH878Test extends BaseTest
{
    public function testSPLObjectHashCollisionOnDoubleMerge()
    {
        $document = $this->getPersistedButDetachedDocument();

        // should be detached as coming from cache
        $state = $this->uow->getDocumentState($document);
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $state);
        // clear to have again clear state
        $this->dm->clear();

        // merge + flush twice the same detached document
        $first = $this->dm->merge($document);
        $this->dm->flush();

        $state = $this->uow->getDocumentState($first);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $state);

        $second = $this->dm->merge($document);
        $this->dm->flush();

        $state = $this->uow->getDocumentState($second);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $state);

        $someOtherDocument = new GH878OtherDocument();

        $state = $this->uow->getDocumentState($someOtherDocument);
        $this->assertEquals(UnitOfWork::STATE_NEW, $state);
    }

    /**
     * @return GH878Document
     */
    private function getPersistedButDetachedDocument() {
        $document = new GH878Document();
        $document->embeddedField = new GH878SubDocument();

        $this->dm->persist($document);
        $this->dm->flush();
        // clear here to simulate a cache
        $this->dm->clear();

        return $document;
    }
}

/** @ODM\Document */
class GH878Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH878SubDocument") */
    public $embeddedField;
}

/** @ODM\EmbeddedDocument */
class GH878SubDocument
{
    /** @ODM\Field(type="string") */
    public $some = '2';
}

/** @ODM\Document */
class GH878OtherDocument
{
    /** @ODM\Id */
    public $id;
}
