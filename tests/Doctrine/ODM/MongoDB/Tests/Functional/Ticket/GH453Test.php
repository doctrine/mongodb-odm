<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function array_values;
use function get_class;
use function sprintf;

class GH453Test extends BaseTest
{
    public function testHashWithStringKeys(): void
    {
        $hash = ['a' => 'x', 'b' => 'y', 'c' => 'z'];

        $doc       = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        self::assertSame($hash, $doc->hash);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($hash['b']);
        $doc       = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');
    }

    public function testHashWithNumericKeys(): void
    {
        $hash = [0 => 'x', 1 => 'y', 2 => 'z'];

        $doc       = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        self::assertSame($hash, $doc->hash);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($hash[1]);
        $doc       = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');
    }

    public function testCollection(): void
    {
        $col = ['x', 'y', 'z'];

        $doc          = new GH453Document();
        $doc->colPush = $col;
        $doc->colSet  = $col;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArrayAndValue($col, $doc->id, 'colPush');
        $this->assertBsonArrayAndValue($col, $doc->id, 'colSet');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        self::assertSame($col, $doc->colPush);
        self::assertSame($col, $doc->colSet);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($col[1]);
        $doc          = $this->dm->merge($doc);
        $doc->colPush = $col;
        $doc->colSet  = $col;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArrayAndValue($col, $doc->id, 'colPush');
        $this->assertBsonArrayAndValue($col, $doc->id, 'colSet');
    }

    public function testEmbedMany(): void
    {
        $colPush     = new ArrayCollection([
            new GH453EmbeddedDocument(),
            new GH453EmbeddedDocument(),
            new GH453EmbeddedDocument(),
        ]);
        $colSet      = $colPush->map(static fn ($v) => clone $v);
        $colSetArray = $colPush->map(static fn ($v) => clone $v);
        $colAddToSet = $colPush->map(static fn ($v) => clone $v);

        $doc                    = new GH453Document();
        $doc->embedManyPush     = $colPush;
        $doc->embedManySet      = $colSet;
        $doc->embedManySetArray = $colSetArray;
        $doc->embedManyAddToSet = $colAddToSet;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        // No need to assert the value of the embedded document structure
        $this->assertBsonArray($doc->id, 'embedManyPush');
        $this->assertBsonArray($doc->id, 'embedManySet');
        $this->assertBsonArray($doc->id, 'embedManySetArray');
        $this->assertBsonArray($doc->id, 'embedManyAddToSet');

        // Check that the value is changed properly
        unset($colPush[1], $colSet[1], $colSetArray[1], $colAddToSet[1]);
        $doc                    = $this->dm->merge($doc);
        $doc->embedManyPush     = $colPush;
        $doc->embedManySet      = $colSet;
        $doc->embedManySetArray = $colSetArray;
        $doc->embedManyAddToSet = $colAddToSet;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($doc->id, 'embedManyPush');
        $this->assertBsonObject($doc->id, 'embedManySet');
        $this->assertBsonArray($doc->id, 'embedManySetArray');
        $this->assertBsonArray($doc->id, 'embedManyAddToSet');
    }

    public function testReferenceMany(): void
    {
        $colPush     = new ArrayCollection([
            new GH453ReferencedDocument(),
            new GH453ReferencedDocument(),
            new GH453ReferencedDocument(),
        ]);
        $colSet      = $colPush->map(static fn ($v) => clone $v);
        $colSetArray = $colPush->map(static fn ($v) => clone $v);
        $colAddToSet = $colPush->map(static fn ($v) => clone $v);

        $dm = $this->dm;

        $colPush->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);

            return true;
        });
        $colSet->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);

            return true;
        });
        $colSetArray->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);

            return true;
        });
        $colAddToSet->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);

            return true;
        });

        $doc                        = new GH453Document();
        $doc->referenceManyPush     = $colPush;
        $doc->referenceManySet      = $colSet;
        $doc->referenceManySetArray = $colSetArray;
        $doc->referenceManyAddToSet = $colAddToSet;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        // No need to assert the value of the referenced document structure
        $this->assertBsonArray($doc->id, 'referenceManyPush');
        $this->assertBsonArray($doc->id, 'referenceManySet');
        $this->assertBsonArray($doc->id, 'referenceManySetArray');
        $this->assertBsonArray($doc->id, 'referenceManyAddToSet');

        // Check that the value is changed properly
        unset($colPush[1], $colSet[1], $colSetArray[1], $colAddToSet[1]);
        $doc->referenceManyPush     = $colPush;
        $doc->referenceManySet      = $colSet;
        $doc->referenceManySetArray = $colSetArray;
        $doc->referenceManyAddToSet = $colAddToSet;

        /* Merging must be done after re-assigning the collections, as the
         * referenced documents must be re-persisted through the merge cascade.
         */
        $doc = $this->dm->merge($doc);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($doc->id, 'referenceManyPush');
        $this->assertBsonObject($doc->id, 'referenceManySet');
        $this->assertBsonArray($doc->id, 'referenceManySetArray');
        $this->assertBsonArray($doc->id, 'referenceManyAddToSet');
    }

    /** @param mixed $documentId */
    private function assertBsonArray($documentId, string $fieldName): void
    {
        $this->assertBsonType(4, $documentId, $fieldName);
    }

    /** @param mixed $documentId */
    private function assertBsonObject($documentId, string $fieldName): void
    {
        $this->assertBsonType(3, $documentId, $fieldName);
    }

    /** @param mixed $documentId */
    private function assertBsonType(int $bsonType, $documentId, string $fieldName): void
    {
        $criteria = ['_id' => $documentId];

        if ($bsonType === 4) {
            // See: https://jira.mongodb.org/browse/SERVER-1475
            $criteria['$where'] = sprintf('Array.isArray(this.%s)', $fieldName);
        } else {
            $criteria[$fieldName] = ['$type' => $bsonType];
        }

        self::assertNotNull($this->dm->getRepository(GH453Document::class)->findOneBy($criteria));
    }

    /**
     * @param mixed $expectedValue
     * @param mixed $documentId
     */
    private function assertBsonArrayAndValue($expectedValue, $documentId, string $fieldName): void
    {
        $this->assertBsonTypeAndValue(4, $expectedValue, $documentId, $fieldName);
    }

    /**
     * @param mixed $expectedValue
     * @param mixed $documentId
     */
    private function assertBsonObjectAndValue($expectedValue, $documentId, string $fieldName): void
    {
        $this->assertBsonTypeAndValue(3, $expectedValue, $documentId, $fieldName);
    }

    /**
     * @param mixed $expectedValue
     * @param mixed $documentId
     */
    private function assertBsonTypeAndValue(int $bsonType, $expectedValue, $documentId, string $fieldName): void
    {
        if ($bsonType === 4) {
            $expectedValue = array_values((array) $expectedValue);
        } elseif ($bsonType === 3) {
            $expectedValue = (object) $expectedValue;
        }

        $criteria = [
            '_id' => $documentId,
            '$and' => [[$fieldName => $expectedValue]],
        ];

        if ($bsonType === 4) {
            // See: https://jira.mongodb.org/browse/SERVER-1475
            $criteria['$and'][] = ['$where' => sprintf('Array.isArray(this.%s)', $fieldName)];
        } else {
            $criteria['$and'][] = [$fieldName => ['$type' => $bsonType]];
        }

        self::assertNotNull($this->dm->getRepository(GH453Document::class)->findOneBy($criteria));
    }
}

/** @ODM\Document */
class GH453Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="hash")
     *
     * @var array<string>
     */
    public $hash;

    /**
     * @ODM\Field(type="collection")
     *
     * @var string[]
     */
    public $colPush;

    /**
     * @ODM\Field(type="collection")
     *
     * @var string[]
     */
    public $colSet;

    /**
     * @ODM\EmbedMany(strategy="pushAll"))
     *
     * @var Collection<int, GH453EmbeddedDocument>
     */
    public $embedManyPush;

    /**
     * @ODM\EmbedMany(strategy="set")
     *
     * @var Collection<int, GH453EmbeddedDocument>
     */
    public $embedManySet;

    /**
     * @ODM\EmbedMany(strategy="setArray")
     *
     * @var Collection<int, GH453EmbeddedDocument>
     */
    public $embedManySetArray;

    /**
     * @ODM\EmbedMany(strategy="addToSet")
     *
     * @var Collection<int, GH453EmbeddedDocument>
     */
    public $embedManyAddToSet;

    /**
     * @ODM\ReferenceMany(strategy="pushAll"))
     *
     * @var Collection<int, GH453ReferencedDocument>
     */
    public $referenceManyPush;

    /**
     * @ODM\ReferenceMany(strategy="set")
     *
     * @var Collection<int, GH453ReferencedDocument>
     */
    public $referenceManySet;

    /**
     * @ODM\ReferenceMany(strategy="setArray")
     *
     * @var Collection<int, GH453ReferencedDocument>
     */
    public $referenceManySetArray;

    /**
     * @ODM\ReferenceMany(strategy="addToSet")
     *
     * @var Collection<int, GH453ReferencedDocument>
     */
    public $referenceManyAddToSet;
}

/** @ODM\EmbeddedDocument */
class GH453EmbeddedDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document */
class GH453ReferencedDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}
