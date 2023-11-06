<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use stdClass;

class TargetDocumentTest extends BaseTestCase
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

    public function testTargetDocumentIsResolvable(): void
    {
        self::expectExceptionMessage("Target document class 'Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass' used in field 'reference' of class 'Doctrine\ODM\MongoDB\Tests\Functional\InvalidTargetDocumentTestDocument' does not exist.");

        $test            = new InvalidTargetDocumentTestDocument();
        $test->reference = new stdClass();
        $this->dm->persist($test);
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

/** @ODM\Document */
class InvalidTargetDocumentTestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass::class)
     *
     * @var object|null
     */
    public $reference;
}
