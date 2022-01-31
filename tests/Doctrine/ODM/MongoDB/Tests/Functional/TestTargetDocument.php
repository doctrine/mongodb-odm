<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class TargetDocumentTest extends BaseTest
{
    public function testMappedSuperClassAsTargetDocument()
    {
        $test            = new TargetDocumentTestDocument();
        $test->reference = new TargetDocumentTestReference();
        $this->dm->persist($test);
        $this->dm->persist($test->reference);
        $this->dm->flush();
    }
}

/** @ODM\Document */
class TargetDocumentTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\TargetDocumentTestReference::class) */
    public $reference;
}

/** @ODM\MappedSuperclass */
abstract class AbstractTargetDocumentTestReference
{
    /** @ODM\Id */
    public $id;
}

/**
 * @ODM\Document
 */
class TargetDocumentTestReference extends AbstractTargetDocumentTestReference
{
}
