<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function array_values;

class XmlMappingSortOrderTest extends BaseTestCase
{
    #[DataProvider('provideReferenceManyFields')]
    public function testReferenceManyIsSortedByXmlMappedSortOrder(string $field): void
    {
        $shelf = new SortOrderXmlShelf();
        $this->dm->persist($shelf);

        // Inserted out of order so that neither natural nor descending order matches
        foreach ([3, 1, 2] as $position) {
            $book = new SortOrderXmlBook($position, $shelf);
            $shelf->books->add($book);
            $this->dm->persist($book);
        }

        $this->dm->flush();
        $this->dm->clear();

        $shelf = $this->dm->find(SortOrderXmlShelf::class, $shelf->id);

        self::assertSame(
            [1, 2, 3],
            array_values(array_map(static fn (SortOrderXmlBook $book): int => $book->position, $shelf->$field->toArray())),
        );
    }

    /** @return array<string, array{string}> */
    public static function provideReferenceManyFields(): array
    {
        return [
            'owning side' => ['books'],
            'inverse side' => ['inverseBooks'],
        ];
    }

    protected static function createMetadataDriverImpl(): MappingDriver
    {
        return new XmlDriver(__DIR__ . '/XmlMappingSortOrder');
    }
}

class SortOrderXmlShelf
{
    public ?string $id = null;

    /** @var Collection<int, SortOrderXmlBook> */
    public Collection $books;

    /** @var Collection<int, SortOrderXmlBook> */
    public Collection $inverseBooks;

    public function __construct()
    {
        $this->books        = new ArrayCollection();
        $this->inverseBooks = new ArrayCollection();
    }
}

class SortOrderXmlBook
{
    public ?string $id = null;

    public function __construct(public int $position, public SortOrderXmlShelf $shelf)
    {
    }
}
