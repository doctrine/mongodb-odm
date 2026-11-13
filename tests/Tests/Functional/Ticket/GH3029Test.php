<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\ReferenceUser;
use PHPUnit\Framework\Attributes\DataProvider;

class GH3029Test extends BaseTestCase
{
    public function testProjectNestedIdentifierOfReferenceAfterLookup(): void
    {
        $team       = new GH3029Team();
        $team->name = 'Team 1';

        $player       = new GH3029Player();
        $player->team = $team;

        $this->dm->persist($team);
        $this->dm->persist($player);
        $this->dm->flush();
        $this->dm->clear();

        $builder = $this->dm->createAggregationBuilder(GH3029Player::class)
            ->lookup('team')
                ->alias('team')
            ->unwind('$team')
            ->project()
                ->includeFields(['team._id', 'team.name']);

        $pipeline = $builder->getPipeline();

        self::assertSame(
            [
                '$lookup' => [
                    'from' => 'GH3029Team',
                    'as' => 'team',
                    'localField' => 'team',
                    'foreignField' => '_id',
                ],
            ],
            $pipeline[0],
        );

        self::assertSame(['$unwind' => '$team'], $pipeline[1]);
        self::assertSame(['team._id' => true, 'team.name' => true], $pipeline[2]['$project']);

        $result = $this->dm->getDocumentCollection(GH3029Player::class)
            ->aggregate($pipeline)
            ->toArray();

        self::assertCount(1, $result);
        self::assertArrayHasKey('_id', $result[0]['team']);
        self::assertSame('Team 1', $result[0]['team']['name']);
    }

    /** @param class-string<GH3029Player|ReferenceUser> $documentName */
    #[DataProvider('providePrepareFieldNameData')]
    public function testPrepareFieldName(string $documentName, string $fieldName, string $expected): void
    {
        $persister = $this->uow->getDocumentPersister($documentName);

        self::assertSame($expected, $persister->prepareFieldName($fieldName));
    }

    /** @return iterable<string, array{class-string<GH3029Player|ReferenceUser>, string, string}> */
    public static function providePrepareFieldNameData(): iterable
    {
        // The identifier property of the target document is named "_id".
        // The database name of an identifier is always "_id", so "team._id"
        // is kept as written. There is no "id" property, so "team.id" is not
        // mapped either and is also kept as written.
        yield 'identifier property named _id, reference field' => [GH3029Player::class, 'team', 'team'];
        yield 'identifier property named _id, nested _id field' => [GH3029Player::class, 'team._id', 'team._id'];
        yield 'identifier property named _id, unmapped field' => [GH3029Player::class, 'team.id', 'team.id'];

        // The identifier property of the target document is named "id", so it
        // is an alias for the reference field. The "_id" database path is a
        // real nested field and is kept as written.
        yield 'identifier property named id, reference field' => [ReferenceUser::class, 'user', 'user'];
        yield 'identifier property named id, nested _id field' => [ReferenceUser::class, 'user._id', 'user._id'];
        yield 'identifier property named id, identifier property' => [ReferenceUser::class, 'user.id', 'user'];
    }
}

#[ODM\Document(collection: 'GH3029Team')]
class GH3029Team
{
    #[ODM\Id]
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.PublicHasUnderscore
    public string $_id;

    #[ODM\Field(type: 'string')]
    public string $name;
}

#[ODM\Document(collection: 'GH3029Player')]
class GH3029Player
{
    #[ODM\Id]
    public string $id;

    #[ODM\ReferenceOne(targetDocument: GH3029Team::class, storeAs: 'id')]
    public GH3029Team $team;
}
