<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\ChangeSets;

use Doctrine\ODM\MongoDB\ChangeSets\ChangeSet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ChangeSetTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);

        self::assertTrue($changeSet->isEmpty());
        self::assertSame([], $changeSet->getFieldNames());
    }

    public function testDocumentIsTheSameInstance(): void
    {
        $document  = new stdClass();
        $changeSet = new ChangeSet($document, []);

        self::assertSame($document, $changeSet->document);
    }

    public function testRecordChangeMarksFieldAsChanged(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);
        $changeSet->recordChange('name', 'Bob');

        self::assertFalse($changeSet->isEmpty());
        self::assertSame(['name'], $changeSet->getFieldNames());
        self::assertTrue($changeSet->hasChangedField('name'));
    }

    public function testOldValueIsAlwaysReadFromOriginalData(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);
        $changeSet->recordChange('name', 'Bob');

        self::assertSame('Alice', $changeSet->getOldValue('name'));
        self::assertSame('Bob', $changeSet->getNewValue('name'));

        // Recording another change to the same field does not shift the old value:
        // it is never taken from a previously recorded new value, only from originalData.
        $changeSet->recordChange('name', 'Carol');

        self::assertSame('Alice', $changeSet->getOldValue('name'));
        self::assertSame('Carol', $changeSet->getNewValue('name'));
    }

    public function testOldValueForFieldMissingFromOriginalDataIsNull(): void
    {
        $changeSet = new ChangeSet(new stdClass(), []);
        $changeSet->recordChange('name', 'Bob');

        self::assertNull($changeSet->getOldValue('name'));
    }

    public function testGetOldValueThrowsForUnchangedField(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);

        $this->expectException(InvalidArgumentException::class);
        $changeSet->getOldValue('name');
    }

    public function testGetNewValueThrowsForUnchangedField(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);

        $this->expectException(InvalidArgumentException::class);
        $changeSet->getNewValue('name');
    }

    public function testApplyToOriginalDataMergesRecordedChanges(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice', 'age' => 30]);
        $changeSet->recordChange('name', 'Bob');

        self::assertSame(['name' => 'Bob', 'age' => 30], $changeSet->applyToOriginalData());
    }

    public function testApplyToOriginalDataWithNoChangesReturnsOriginalData(): void
    {
        $originalData = ['name' => 'Alice', 'age' => 30];
        $changeSet    = new ChangeSet(new stdClass(), $originalData);

        self::assertSame($originalData, $changeSet->applyToOriginalData());
    }

    public function testToArrayReturnsOldStyleShape(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice', 'age' => 30]);
        $changeSet->recordChange('name', 'Bob');

        self::assertSame(['name' => ['Alice', 'Bob']], $changeSet->toArray());
    }

    public function testToArrayIsEmptyWhenNoChangesRecorded(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);

        self::assertSame([], $changeSet->toArray());
    }

    public function testMergeCopiesChangesFromOther(): void
    {
        $document = new stdClass();
        $existing = new ChangeSet($document, ['name' => 'Alice', 'age' => 30]);
        $existing->recordChange('name', 'Bob');

        $other = new ChangeSet($document, ['name' => 'Alice', 'age' => 30]);
        $other->recordChange('age', 31);

        $existing->merge($other);

        self::assertSame(['name', 'age'], $existing->getFieldNames());
        self::assertSame('Bob', $existing->getNewValue('name'));
        self::assertSame(31, $existing->getNewValue('age'));
    }

    public function testMergeOverwritesNewValueButKeepsOwnOldValue(): void
    {
        $document = new stdClass();
        $existing = new ChangeSet($document, ['name' => 'Alice']);
        $existing->recordChange('name', 'Bob');

        $other = new ChangeSet($document, ['name' => 'Bob']);
        $other->recordChange('name', 'Carol');

        $existing->merge($other);

        self::assertSame('Alice', $existing->getOldValue('name'));
        self::assertSame('Carol', $existing->getNewValue('name'));
    }
}
