<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class TargetDocumentTest extends BaseTest
{
    /** @doesNotPerformAssertions */
    public function testMappedSuperClassAsTargetDocument(): void
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\TargetDocumentTestReference::class)
     *
     * @var TargetDocumentTestReference|null
     */
    public $reference;
}

/** @ODM\MappedSuperclass */
abstract class AbstractTargetDocumentTestReference
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document */
class TargetDocumentTestReference extends AbstractTargetDocumentTestReference
{
}
