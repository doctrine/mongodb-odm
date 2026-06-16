<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;

use function sprintf;

class CollectionPerClassInheritanceDeprecationTest extends BaseTestCase
{
    use CaptureDeprecationMessages;

    public function testCollectionPerClassInheritanceEmitsDeprecation(): void
    {
        $factory = new ClassMetadataFactory();
        $factory->setDocumentManager($this->dm);
        $factory->setConfiguration($this->dm->getConfiguration());

        $this->captureDeprecationMessages(
            static fn () => $factory->getMetadataFor(CollectionPerClassDeprecatedDocument::class),
            $errors,
        );

        self::assertContains(
            sprintf('Since doctrine/mongodb-odm 2.17: COLLECTION_PER_CLASS inheritance type used in class "%s" is deprecated with no replacement. Remove the InheritanceType attribute/annotation: each class is already mapped to its own collection.', CollectionPerClassDeprecatedDocument::class),
            $errors,
        );
    }
}

#[ODM\Document]
#[ODM\InheritanceType('COLLECTION_PER_CLASS')]
class CollectionPerClassDeprecatedDocument
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}
