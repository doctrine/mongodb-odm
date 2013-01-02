<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH453Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testHashWithStringKeys()
    {
        $hash = array('a' => 'x', 'b' => 'y', 'c' => 'z');

        $doc = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObject($hash, $doc->id, 'hash');

        unset($hash['b']);
        $doc = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObject($hash, $doc->id, 'hash');
    }

    public function testHashWithNumericKeys()
    {
        $hash = array(0 => 'x', 1 => 'y', 2 => 'z');

        $doc = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObject($hash, $doc->id, 'hash');

        unset($hash[1]);
        $doc = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObject($hash, $doc->id, 'hash');
    }

    public function testCollection()
    {
        $col = array('x', 'y', 'z');

        $doc = new GH453Document();
        $doc->colPush = $col;
        $doc->colSet = $col;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($col, $doc->id, 'colPush');
        $this->assertBsonArray($col, $doc->id, 'colSet');

        unset($col[1]);
        $doc = $this->dm->merge($doc);
        $doc->colPush = $col;
        $doc->colSet = $col;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($col, $doc->id, 'colPush');
        $this->assertBsonArray($col, $doc->id, 'colSet');
    }

    private function assertBsonArray($expectedValue, $documentId, $fieldName)
    {
        $this->assertBsonTypeAndValue(4, $expectedValue, $documentId, $fieldName);
    }

    private function assertBsonObject($expectedValue, $documentId, $fieldName)
    {
        $this->assertBsonTypeAndValue(3, $expectedValue, $documentId, $fieldName);
    }

    private function assertBsonTypeAndValue($bsonType, $expectedValue, $documentId, $fieldName)
    {
        if ($expectedValue instanceof Collection) {
            $expectedValue = $expectedValue->toArray();
        }

        if (4 === $bsonType) {
            $expectedValue = array_values((array) $expectedValue);
        } elseif (3 === $bsonType) {
            $expectedValue = (object) $expectedValue;
        }

        $criteria = array(
            '_id' => $documentId,
            '$and' => array(array($fieldName => $expectedValue)),
        );

        if (4 === $bsonType) {
            // See: https://jira.mongodb.org/browse/SERVER-1475
            $criteria['$and'][] = array('$where' => sprintf('Array.isArray(this.%s)', $fieldName));
        } else {
            $criteria['$and'][] = array($fieldName => array('$type' => $bsonType));
        }

        $this->assertNotNull($this->dm->getRepository(__NAMESPACE__ . '\GH453Document')->findOneBy($criteria));
    }
}

/** @ODM\Document */
class GH453Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Hash */
    public $hash;

    /** @ODM\Collection(strategy="pushAll")) */
    public $colPush;

    /** @ODM\Collection(strategy="set") */
    public $colSet;

    /** @ODM\EmbedMany(strategy="pushAll")) */
    public $embedManyPush;

    /** @ODM\EmbedMany(strategy="set") */
    public $embedManySet;

    /** @ODM\ReferenceMany(strategy="pushAll")) */
    public $refManyPush;

    /** @ODM\ReferenceMany(strategy="set") */
    public $refManySet;
}

/** @ODM\Document */
class GH453EmbeddedDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document */
class GH453ReferencedDocument
{
    /** @ODM\Id */
    public $id;
}
