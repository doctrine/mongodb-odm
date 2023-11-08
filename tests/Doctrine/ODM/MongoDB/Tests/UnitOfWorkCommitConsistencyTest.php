<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Documents\Address;
use Documents\ForumUser;
use Documents\FriendUser;
use Documents\User;
use MongoDB\BSON\ObjectId;
use Throwable;

class UnitOfWorkCommitConsistencyTest extends BaseTestCase
{
    public function tearDown(): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => 'off',
        ]);

        parent::tearDown();
    }

    public function testErrorDuringInsertKeepsFailingInsertions(): void
    {
        $firstUser           = new ForumUser();
        $firstUser->username = 'alcaeus';
        $this->uow->persist($firstUser);

        $secondUser           = new ForumUser();
        $secondUser->username = 'jmikola';
        $this->uow->persist($secondUser);

        $friendUser = new FriendUser('GromNaN');
        $this->uow->persist($friendUser);

        // Add failpoint to let the first insert command fail. This affects the ForumUser documents
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['insert'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        // Wrong behaviour: the insertions should still be scheduled
        self::assertFalse($this->uow->isScheduledForInsert($firstUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));

        self::assertFalse($this->uow->isScheduledForInsert($secondUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));

        self::assertTrue($this->uow->isScheduledForInsert($friendUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($friendUser));
    }

    public function testErrorDuringInsertKeepsFailingInsertionsForDocument(): void
    {
        // Create a unique index on the collection to let the second insert fail
        $collection = $this->dm->getDocumentCollection(ForumUser::class);
        $collection->createIndex(['username' => 1], ['unique' => true]);

        $firstUser           = new ForumUser();
        $firstUser->username = 'alcaeus';
        $this->uow->persist($firstUser);

        $secondUser           = new ForumUser();
        $secondUser->username = 'alcaeus';
        $this->uow->persist($secondUser);

        $thirdUser           = new ForumUser();
        $thirdUser->username = 'jmikola';
        $this->uow->persist($thirdUser);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // One user inserted, the second insert failed, the last was skipped
        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        // First user was inserted and changeset cleared
        self::assertFalse($this->uow->isScheduledForInsert($firstUser));
        // Wrong behaviour: changeset should be empty
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));

        // Wrong behaviour: user should still be scheduled for insertion
        self::assertFalse($this->uow->isScheduledForInsert($secondUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));

        // Wrong behaviour: user should still be scheduled for insertion
        self::assertFalse($this->uow->isScheduledForInsert($thirdUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($thirdUser));
    }

    public function testErrorDuringInsertWithEmbeddedDocumentKeepsInsertions(): void
    {
        // Create a unique index on the collection to let the second insert fail
        $collection = $this->dm->getDocumentCollection(User::class);
        $collection->createIndex(['username' => 1], ['unique' => true]);

        $firstAddress = new Address();
        $firstAddress->setCity('Olching');
        $firstUser = new User();
        $firstUser->setUsername('alcaeus');
        $firstUser->setAddress($firstAddress);

        $secondAddress = new Address();
        $secondAddress->setCity('Olching');
        $secondUser = new User();
        $secondUser->setUsername('alcaeus');
        $secondUser->setAddress($secondAddress);

        $this->uow->persist($firstUser);
        $this->uow->persist($secondUser);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // First document inserted, second failed due to index error
        self::assertSame(1, $collection->countDocuments());

        $this->assertFalse($this->uow->isScheduledForInsert($firstUser));
        // Wrong behaviour: changeset should be cleared but isn't
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));

        // Wrong behaviour: document should no longer be scheduled for insertion and changeset cleared
        $this->assertTrue($this->uow->isScheduledForInsert($firstAddress));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($firstAddress));

        // Wrong behaviour: document should still be scheduled for insertion
        $this->assertFalse($this->uow->isScheduledForInsert($secondUser));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));
        $this->assertTrue($this->uow->isScheduledForInsert($secondAddress));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($secondAddress));
    }

    public function testErrorDuringUpsertDropsFailingUpserts(): void
    {
        $user           = new ForumUser();
        $user->id       = new ObjectId(); // Specifying an identifier makes this an upsert
        $user->username = 'alcaeus';
        $this->uow->persist($user);

        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['update'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // No document was inserted
        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        // Wrong behaviour: document should still be scheduled
        self::assertFalse($this->uow->isScheduledForUpsert($user));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testErrorDuringUpdateKeepsFailingUpdate(): void
    {
        $user           = new ForumUser();
        $user->username = 'alcaeus';
        $this->uow->persist($user);
        $this->uow->commit();

        $user->username = 'jmikola';

        // Make sure update command fails once
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['update'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // The update is kept, user data is not changed
        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertTrue($this->uow->isScheduledForUpdate($user));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testErrorDuringUpdateWithNewEmbeddedDocumentKeepsFailingChangeset(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');

        $this->uow->persist($user);
        $this->uow->commit();

        $address = new Address();
        $address->setCity('Olching');
        $user->setAddress($address);

        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['update'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForInsert($address));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testSuccessfulUpdateWithNewEmbeddedDocumentClearsChangesets(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');

        $this->uow->persist($user);
        $this->uow->commit();

        $address = new Address();
        $address->setCity('Olching');
        $user->setAddress($address);

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForInsert($address));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testErrorDuringUpdateOfEmbeddedDocumentKeepsFailingChangeset(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $address->setCity('Munich');

        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['update'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForUpdate($address));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testSuccessfulUpdateOfEmbeddedDocumentClearsChangesets(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $address->setCity('Munich');

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForUpdate($address));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testErrorDuringUpdateWithRemovedEmbeddedDocumentKeepsFailingChangeset(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $user->removeAddress();

        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['update'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForDelete($address));

        // As $address is orphaned after changeset computation, it is removed from the identity map
        $this->assertFalse($this->uow->isInIdentityMap($address));
    }

    public function testSuccessfulUpdateWithRemovedEmbeddedDocumentClearsChangesets(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $user->removeAddress();

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForDelete($address));
        $this->assertFalse($this->uow->isInIdentityMap($address));
    }

    public function testErrorDuringDeleteKeepsFailingDelete(): void
    {
        $user           = new ForumUser();
        $user->username = 'alcaeus';
        $this->uow->persist($user);
        $this->uow->commit();

        $this->uow->remove($user);

        // Make sure delete command fails once
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['delete'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // The document still exists, the deletion is still scheduled
        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertTrue($this->uow->isScheduledForDelete($user));
    }

    public function testFailingDeleteWithEmbeddedDocumentKeepsChangeset(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $this->uow->remove($user);

        // Make sure delete command fails once
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 10107, // Not primary
                'failCommands' => ['delete'],
            ],
        ]);

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable) {
        }

        // The document still exists, the deletion is still scheduled
        self::assertSame(
            1,
            $this->dm->getDocumentCollection(User::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertTrue($this->uow->isScheduledForDelete($user));
        // Wrong behaviour: embedded document should be scheduled for deletion
        self::assertFalse($this->uow->isScheduledForDelete($address));
    }

    public function testSuccessfulDeleteWithEmbeddedDocumentClearsChangeset(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $this->uow->remove($user);

        $this->uow->commit();

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(User::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertFalse($this->uow->isScheduledForDelete($user));
        self::assertFalse($this->uow->isScheduledForDelete($address));
    }
}
