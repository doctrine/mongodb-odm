<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH897Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testRecomputeSingleDocumentChangesetForManagedDocumentWithoutChangeset()
    {
        $documentA = new GH897A();
        $documentA->name = 'a';
        $documentB = new GH897B();
        $documentB->name = 'b';

        $this->dm->persist($documentA);
        $this->dm->persist($documentB);
        $this->dm->flush();
        $this->dm->clear();

        $documentA = $this->dm->find(__NAMESPACE__.'\GH897A', $documentA->id);
        $documentB = $this->dm->find(__NAMESPACE__.'\GH897B', $documentB->id);
        $documentB->refOne = $documentA;

        /* Necessary to inject DocumentManager since it is not currently
         * provided in the lifecycle event arguments.
         */
        $documentB->dm = $this->dm;

        $this->dm->flush();
        $this->dm->clear();

        $documentA = $this->dm->find(__NAMESPACE__.'\GH897A', $documentA->id);

        $this->assertSame('a-changed', $documentA->name);
    }
}

/** @ODM\Document */
class GH897A
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class GH897B
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument="GH897A") */
    public $refOne;

    public $dm;

    /** @ODM\PreFlush */
    public function preFlush()
    {
        if ( ! $this->refOne instanceof GH897A) {
            return;
        }

        $documentA = $this->refOne;
        $documentA->name .= '-changed';

        $class = $this->dm->getClassMetadata(get_class($documentA));
        $this->dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($class, $documentA);
    }
}
