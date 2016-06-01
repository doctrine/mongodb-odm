<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;

class GH1418Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testManualHydrateAndPersist()
    {
        $document = new GH1418Document;
        $class = $this->dm->getClassMetadata(get_class($document));
        $this->dm->getHydratorFactory()->hydrate($document, array(
          '_id' => 1,
          'name' => 'maciej',
          'embedOne' => ['name' => 'maciej'],
          'embedMany' => [
              ['name' => 'maciej']
          ],
        ), [ Query::HINT_READ_ONLY => true ]);

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(__NAMESPACE__.'\GH1418Document')->find(1);
        $this->assertEquals(1, $document->id);
        $this->assertEquals('maciej', $document->embedOne->name);
        $this->assertEquals(1, $document->embedMany->count());
        $this->assertEquals('maciej', $document->embedMany->first()->name);
    }

    public function testManualHydrateAndMerge()
    {
        $document = new GH1418Document;
        $class = $this->dm->getClassMetadata(get_class($document));
        $this->dm->getHydratorFactory()->hydrate($document, array(
          '_id' => 1,
          'name' => 'maciej',
          'embedOne' => ['name' => 'maciej'],
          'embedMany' => [
              ['name' => 'maciej']
          ],
        ), [ Query::HINT_READ_ONLY => true ]);

        $this->dm->merge($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(__NAMESPACE__.'\GH1418Document')->find(1);
        $this->assertEquals(1, $document->id);
        $this->assertEquals('maciej', $document->embedOne->name);
        $this->assertEquals(1, $document->embedMany->count());
        $this->assertEquals('maciej', $document->embedMany->first()->name);
    }

    public function testManualHydrateAndPersistEmbedEmpty()
    {
        $document = new GH1418Document;
        $class = $this->dm->getClassMetadata(get_class($document));
        $this->dm->getHydratorFactory()->hydrate($document, array(
          '_id' => 1,
          'name' => 'maciej',
        ), [ Query::HINT_READ_ONLY => true ]);
        // This is a work around to trigger persisting the embedded documents
        // until a solution is implemented
        $this->dm->getUnitOfWork()->getDocumentState($document->embedOne);
        $this->dm->getUnitOfWork()->getDocumentState($document->embedMany);

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();
        // If no error is thrown, the test has passed
    }
}

/** @ODM\Document */
class GH1418Document
{
    /** @ODM\Id(strategy="none") */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH1418Embedded") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="GH1418Embedded") */
    public $embedMany;
}

/** @ODM\EmbeddedDocument */
class GH1418Embedded
{
    /** @ODM\Field(type="string") */
    public $name;
}
