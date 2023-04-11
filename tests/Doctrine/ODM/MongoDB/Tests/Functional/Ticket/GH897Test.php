<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH897Test extends BaseTestCase
{
    public function testRecomputeSingleDocumentChangesetForManagedDocumentWithoutChangeset(): void
    {
        $documentA       = new GH897A();
        $documentA->name = 'a';
        $documentB       = new GH897B();
        $documentB->name = 'b';

        $this->dm->persist($documentA);
        $this->dm->persist($documentB);
        $this->dm->flush();
        $this->dm->clear();

        $documentA         = $this->dm->find(GH897A::class, $documentA->id);
        $documentB         = $this->dm->find(GH897B::class, $documentB->id);
        $documentB->refOne = $documentA;

        /* Necessary to inject DocumentManager since it is not currently
         * provided in the lifecycle event arguments.
         */
        $documentB->dm = $this->dm;

        $this->dm->flush();
        $this->dm->clear();

        $documentA = $this->dm->find(GH897A::class, $documentA->id);

        self::assertSame('a-changed', $documentA->name);
    }
}

/** @ODM\Document */
class GH897A
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class GH897B
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=GH897A::class)
     *
     * @var GH897A|null
     */
    public $refOne;

    /** @var DocumentManager|null */
    public $dm;

    /** @ODM\PreFlush */
    public function preFlush(): void
    {
        if (! $this->refOne instanceof GH897A) {
            return;
        }

        $documentA        = $this->refOne;
        $documentA->name .= '-changed';

        $class = $this->dm->getClassMetadata($documentA::class);
        $this->dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($class, $documentA);
    }
}
