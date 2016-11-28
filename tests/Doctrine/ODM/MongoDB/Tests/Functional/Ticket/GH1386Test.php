<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1386Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testInverseReferencesAreNotStoredInEmbeddedDocument()
    {
        $referencing = new GH1386Referencing();
        $this->dm->persist($referencing);

        $referenced = new GH1386Referenced();
        $this->dm->persist($referenced);

        $referencing->references->add($referenced);

        $this->dm->flush();

        $this->dm->refresh($referenced);

        $parent = new GH1386Embedding($referenced);
        $this->dm->persist($parent);

        $this->dm->flush();

        $result = $this->dm->getRepository(get_class($parent))
            ->createQueryBuilder()
            ->field('id')
            ->equals($parent->id)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertArrayHasKey('embedded', $result);
        $this->assertArrayNotHasKey('inverseReferenceOne', $result['embedded']);
        $this->assertArrayNotHasKey('inverseReferenceMany', $result['embedded']);

        $this->dm->clear();

        // Ensure inverse references are still properly resolved
        $parent = $this->dm->find(get_class($parent), $parent->id);
        $this->assertNotNull($parent);

        $this->assertNotNull($parent->embedded->inverseReferenceOne);
        $this->assertCount(1, $parent->embedded->inverseReferenceMany);
    }
}

/** @ODM\Document */
class GH1386Referencing
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany */
    public $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }
}

/** @ODM\Document */
class GH1386Embedding
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH1386Referenced") */
    public $embedded;

    public function __construct(GH1386Referenced $embedded)
    {
        $this->embedded = $embedded;
        $this->embedded->inverseReferenceMany->initialize();
    }
}

/** @ODM\Document */
class GH1386Referenced
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="GH1386Referencing", mappedBy="references") */
    public $inverseReferenceOne;

    /** @ODM\ReferenceMany(targetDocument="GH1386Referencing", mappedBy="references") */
    public $inverseReferenceMany;
}
