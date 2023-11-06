<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
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
        self::expectExceptionObject(
            MappingException::invalidTargetDocument(
                // @phpstan-ignore-next-line class.notFound
                SomeInvalidClass::class,
                InvalidTargetDocumentTestDocument::class,
                'reference',
            ),
        );

        $test            = new InvalidTargetDocumentTestDocument();
        $test->reference = new stdClass();
        $this->dm->persist($test);
    }

    public function testDiscriminatorTargetIsResolvable(): void
    {
        self::expectExceptionObject(
            MappingException::invalidClassInReferenceDiscriminatorMap(
                // @phpstan-ignore-next-line class.notFound
                SomeInvalidClass::class,
                InvalidDiscriminatorTargetsTestDocument::class,
                'reference',
            ),
        );

        $test            = new InvalidDiscriminatorTargetsTestDocument();
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
     * @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass")
     *
     * @var object|null
     */
    public $reference;
}


/** @ODM\Document */
class InvalidDiscriminatorTargetsTestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(discriminatorField="referencedClass", discriminatorMap={"Foo"="Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass"})
     *
     * @var object|null
     */
    public $reference;
}
