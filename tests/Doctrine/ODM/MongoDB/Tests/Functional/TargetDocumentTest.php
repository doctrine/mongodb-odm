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
                'Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass',
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
                'Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass',
                InvalidDiscriminatorTargetsTestDocument::class,
                'reference',
            ),
        );

        $test            = new InvalidDiscriminatorTargetsTestDocument();
        $test->reference = new stdClass();
        $this->dm->persist($test);
    }
}

#[ODM\Document]
class TargetDocumentTestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var TargetDocumentTestReference|null */
    #[ODM\ReferenceOne(targetDocument: TargetDocumentTestReference::class)]
    public $reference;
}

#[ODM\MappedSuperclass]
abstract class AbstractTargetDocumentTestReference
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
class TargetDocumentTestReference extends AbstractTargetDocumentTestReference
{
}

#[ODM\Document]
class InvalidTargetDocumentTestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var object|null */
    #[ODM\ReferenceOne(targetDocument: 'Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass')]
    public $reference;
}


#[ODM\Document]
class InvalidDiscriminatorTargetsTestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var object|null */
    #[ODM\ReferenceOne(discriminatorField: 'referencedClass', discriminatorMap: ['Foo' => 'Doctrine\ODM\MongoDB\Tests\Functional\SomeInvalidClass'])]
    public $reference;
}
