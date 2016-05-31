<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;

class GH1350Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testManualHydrateAndMerge()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\GH1350Document');
        $document = $class->newInstance();
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

        $document = $this->dm->getRepository(__NAMESPACE__.'\GH1350Document')->findOneById(1);
        $this->assertEquals(1, $document->id);
        $this->assertEquals('maciej', $document->embedOne->name);
        $this->assertEquals(1, $document->embedMany->count());
    }
}

/** @ODM\Document */
class GH1350Document
{
    /** @ODM\Id(strategy="none") */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH1350Embedded", strategy="set") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="GH1350Embedded", strategy="set") */
    public $embedMany;
}

/** @ODM\EmbeddedDocument */
class GH1350Embedded
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name) {
      $this->name = $name;
    }
}
