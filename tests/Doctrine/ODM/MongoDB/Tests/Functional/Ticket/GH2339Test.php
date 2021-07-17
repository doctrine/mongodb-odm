<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test\InlinedDocument;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test\ParentDocument;

use function phpversion;
use function version_compare;

class GH2339Test extends BaseTest
{
    public function testObjectIdInterfaceInEmbeddedDocuments()
    {
        $parent  = new ParentDocument();
        $inlined = new InlinedDocument();

        $parent->addInlined($inlined);

        $this->dm->persist($parent);
        $this->dm->flush();

        $document = $this->dm->find(ParentDocument::class, $parent->getId());

        $this->assertEquals($parent->getId(), $document->getId());
        $this->assertNotEmpty($document->getEmbedded());
        $this->assertInstanceOf(InlinedDocument::class, $document->getEmbedded()[0]);
        $this->assertEquals($inlined->getId(), $document->getEmbedded()[0]->getId());
    }
}
