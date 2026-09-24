<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\OtherSubProject;
use Documents\Profile;
use Documents\Project;
use Documents\User;
use MongoDB\BSON\ObjectId;

use function array_keys;

/**
 * Unit-style coverage for CriteriaPreparer, constructed directly rather than
 * through DocumentPersister. This complements (rather than replaces) the
 * existing black-box coverage of query preparation spread across
 * Persisters\DocumentPersisterFilterTest, Query\BuilderTest and the
 * Aggregation\* test suites, which exercise this logic indirectly via
 * DocumentPersister's delegating methods.
 */
class CriteriaPreparerTest extends BaseTestCase
{
    /** @phpstan-param class-string $class */
    private function createPreparer(string $class): CriteriaPreparer
    {
        $metadata = $this->dm->getClassMetadata($class);

        return new CriteriaPreparer($this->dm, $this->uow->getPersistenceBuilder(), $metadata, new CriteriaMerger());
    }

    public function testPrepareFieldNameTranslatesIdentifier(): void
    {
        self::assertSame('_id', $this->createPreparer(User::class)->prepareFieldName('id'));
    }

    public function testPrepareFieldNameTranslatesMappedFieldWithCustomDbName(): void
    {
        self::assertSame('disable-at', $this->createPreparer(User::class)->prepareFieldName('disabledAt'));
    }

    public function testPrepareFieldNameLeavesUnmappedFieldUnchanged(): void
    {
        self::assertSame('somethingUnmapped', $this->createPreparer(User::class)->prepareFieldName('somethingUnmapped'));
    }

    public function testPrepareProjectionTranslatesKeysOnly(): void
    {
        $result = $this->createPreparer(User::class)->prepareProjection([
            'disabledAt' => 1,
            'username' => 0,
        ]);

        self::assertSame(['disable-at' => 1, 'username' => 0], $result);
    }

    public function testPrepareSortTranslatesFieldNamesAndDirections(): void
    {
        $result = $this->createPreparer(User::class)->prepareSort([
            'disabledAt' => 'asc',
            'username' => -1,
        ]);

        self::assertSame(['disable-at' => 1, 'username' => -1], $result);
    }

    public function testAddDiscriminatorToPreparedQueryIsNoopWithoutDiscriminatorField(): void
    {
        $query = $this->createPreparer(User::class)->addDiscriminatorToPreparedQuery(['username' => 'alice']);

        self::assertSame(['username' => 'alice'], $query);
    }

    public function testAddDiscriminatorToPreparedQueryDoesNotOverrideExistingField(): void
    {
        $query = $this->createPreparer(Project::class)->addDiscriminatorToPreparedQuery(['type' => 'explicit']);

        self::assertSame(['type' => 'explicit'], $query);
    }

    public function testAddDiscriminatorToPreparedQueryUsesSingleValueForLeafClass(): void
    {
        $query = $this->createPreparer(OtherSubProject::class)->addDiscriminatorToPreparedQuery([]);

        self::assertSame(['type' => 'other-sub-project'], $query);
    }

    public function testAddDiscriminatorToPreparedQueryUsesInForClassWithSubclasses(): void
    {
        $query = $this->createPreparer(Project::class)->addDiscriminatorToPreparedQuery([]);

        self::assertArrayHasKey('type', $query);
        self::assertArrayHasKey('$in', $query['type']);
        self::assertEqualsCanonicalizing(
            ['project', 'sub-project', 'other-sub-project'],
            $query['type']['$in'],
        );
    }

    public function testAddFilterToPreparedQueryMergesEnabledFilterCriteria(): void
    {
        $filterCollection = $this->dm->getFilterCollection();
        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'alice');

        $query = $this->createPreparer(User::class)->addFilterToPreparedQuery(['username' => 'bob']);

        self::assertSame(
            ['$and' => [['username' => 'bob'], ['username' => 'alice']]],
            $query,
        );
    }

    public function testAddFilterToPreparedQueryIsNoopWithoutEnabledFilters(): void
    {
        $query = $this->createPreparer(User::class)->addFilterToPreparedQuery(['username' => 'bob']);

        self::assertSame(['username' => 'bob'], $query);
    }

    public function testPrepareQueryOrNewObjTranslatesIdentifierField(): void
    {
        $id     = new ObjectId();
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['id' => (string) $id]);

        self::assertEquals(['_id' => $id], $result);
    }

    public function testPrepareQueryOrNewObjTranslatesFieldNameAndConvertsValue(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['deletedAt' => '5']);

        self::assertSame(['deletedAt' => 5], $result);
    }

    public function testPrepareQueryOrNewObjLeavesUnmappedDottedFieldUnchanged(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['somethingUnmapped.nested' => 'value']);

        self::assertSame(['somethingUnmapped.nested' => 'value'], $result);
    }

    public function testPrepareQueryOrNewObjPreparesEmbeddedDocumentValue(): void
    {
        $address = new Address();
        $address->setAddress('221B Baker Street');
        $address->setCity('London');

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['address' => $address]);

        self::assertSame('221B Baker Street', $result['address']['address']);
        self::assertSame('London', $result['address']['city']);
    }

    public function testPrepareQueryOrNewObjPreparesReferenceOneWithDefaultStoreAs(): void
    {
        $profile = new Profile();
        $this->dm->persist($profile);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['profile' => $profile]);

        self::assertEqualsCanonicalizing(['profile.$id', 'profile.$ref'], array_keys($result));
        self::assertEquals(new ObjectId($profile->getProfileId()), $result['profile.$id']);
    }

    public function testPrepareQueryOrNewObjPreparesReferenceOneWithStoreAsId(): void
    {
        $account = new Account();
        $this->dm->persist($account);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['accountSimple' => $account]);

        self::assertEquals(['accountSimple' => new ObjectId($account->getId())], $result);
    }

    public function testPrepareQueryOrNewObjPreparesReferenceManyWithDefaultStoreAsUsesElemMatch(): void
    {
        $group = new Group('Admins');
        $this->dm->persist($group);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['groups' => $group]);

        self::assertArrayHasKey('groups', $result);
        self::assertArrayHasKey('$elemMatch', $result['groups']);
        self::assertEquals(new ObjectId($group->getId()), $result['groups']['$elemMatch']['$id']);
    }

    public function testPrepareQueryOrNewObjPreparesReferenceManyWithStoreAsId(): void
    {
        $group = new Group('Admins');
        $this->dm->persist($group);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['groupsSimple' => $group]);

        self::assertEquals(['groupsSimple' => new ObjectId($group->getId())], $result);
    }

    public function testPrepareQueryOrNewObjAcceptsRawIdentifierForStoreAsIdReference(): void
    {
        $group = new Group('Admins');
        $this->dm->persist($group);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['groupsSimple' => $group->getId()]);

        self::assertEquals(['groupsSimple' => new ObjectId($group->getId())], $result);
    }

    public function testPrepareQueryOrNewObjPreparesPositionalOperatorOnReferenceMany(): void
    {
        $group = new Group('Admins');
        $this->dm->persist($group);
        $this->dm->flush();

        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['groups.$.id' => $group->getId()]);

        self::assertEquals(['groups.$.$id' => new ObjectId($group->getId())], $result);
    }

    public function testPrepareQueryOrNewObjRecursesIntoLogicalOperators(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj([
            '$or' => [
                ['disabledAt' => null],
                ['username' => 'bob'],
            ],
        ]);

        self::assertSame(
            [
                '$or' => [
                    ['disable-at' => null],
                    ['username' => 'bob'],
                ],
            ],
            $result,
        );
    }

    public function testPrepareQueryOrNewObjRecursesIntoNestedOperator(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj([
            '$not' => ['username' => 'bob'],
        ]);

        self::assertSame(['$not' => ['username' => 'bob']], $result);
    }

    public function testPrepareQueryOrNewObjLeavesExistsOperatorUnconverted(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj([
            'deletedAt' => ['$exists' => true],
        ]);

        self::assertSame(['deletedAt' => ['$exists' => true]], $result);
    }

    public function testPrepareQueryOrNewObjConvertsInOperatorArrayValues(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj([
            'deletedAt' => ['$in' => ['1', '2', '3']],
        ]);

        self::assertSame(['deletedAt' => ['$in' => [1, 2, 3]]], $result);
    }

    public function testPrepareQueryOrNewObjRecursesIntoEmbedManyDottedField(): void
    {
        $result = $this->createPreparer(User::class)->prepareQueryOrNewObj(['phonenumbers.phonenumber' => '555-0100']);

        self::assertSame(['phonenumbers.phonenumber' => '555-0100'], $result);
    }
}
