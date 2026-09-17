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

    public function testGetDocumentReturnsTheSameInstance(): void
    {
        $document  = new stdClass();
        $changeSet = new ChangeSet($document, []);

        self::assertSame($document, $changeSet->getDocument());
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

    public function testSetNewValueOverwritesAnAlreadyRecordedChange(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);
        $changeSet->recordChange('name', 'Bob');
        $changeSet->setNewValue('name', 'Carol');

        self::assertSame('Alice', $changeSet->getOldValue('name'));
        self::assertSame('Carol', $changeSet->getNewValue('name'));
    }

    public function testSetNewValueThrowsIfFieldHasNotChanged(): void
    {
        $changeSet = new ChangeSet(new stdClass(), ['name' => 'Alice']);

        $this->expectException(InvalidArgumentException::class);
        $changeSet->setNewValue('name', 'Bob');
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
}
