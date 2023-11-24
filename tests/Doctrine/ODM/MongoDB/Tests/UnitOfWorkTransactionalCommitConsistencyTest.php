<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Documents\Address;
use Documents\ForumUser;
use Documents\FriendUser;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Driver\Exception\BulkWriteException;
use Throwable;

class UnitOfWorkTransactionalCommitConsistencyTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipTestIfNoTransactionSupport();
    }

    public function tearDown(): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => 'off',
        ]);

        parent::tearDown();
    }

    public function testFatalInsertError(): void
    {
        $firstUser           = new ForumUser();
        $firstUser->username = 'alcaeus';
        $this->uow->persist($firstUser);

        $secondUser           = new ForumUser();
        $secondUser->username = 'jmikola';
        $this->uow->persist($secondUser);

        $friendUser = new FriendUser('GromNaN');
        $this->uow->persist($friendUser);

        $this->createFatalFailPoint('insert');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(FriendUser::class)->countDocuments(),
        );

        self::assertTrue($this->uow->isScheduledForInsert($firstUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));

        self::assertTrue($this->uow->isScheduledForInsert($secondUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));

        self::assertTrue($this->uow->isScheduledForInsert($friendUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($friendUser));
    }

    public function testTransientInsertError(): void
    {
        $firstUser           = new ForumUser();
        $firstUser->username = 'alcaeus';
        $this->uow->persist($firstUser);

        $secondUser           = new ForumUser();
        $secondUser->username = 'jmikola';
        $this->uow->persist($secondUser);

        $friendUser = new FriendUser('GromNaN');
        $this->uow->persist($friendUser);

        // Add a failpoint that triggers a transient error. The transaction will be retried and succeeds
        $this->createTransientFailPoint('insert');

        $this->uow->commit();

        self::assertSame(
            2,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertSame(
            1,
            $this->dm->getDocumentCollection(FriendUser::class)->countDocuments(),
        );

        self::assertFalse($this->uow->isScheduledForInsert($firstUser));
        self::assertEquals([], $this->uow->getDocumentChangeSet($firstUser));

        self::assertFalse($this->uow->isScheduledForInsert($secondUser));
        self::assertEquals([], $this->uow->getDocumentChangeSet($secondUser));

        self::assertFalse($this->uow->isScheduledForInsert($friendUser));
        self::assertEquals([], $this->uow->getDocumentChangeSet($friendUser));
    }

    public function testDuplicateKeyError(): void
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
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(11000, $e->getCode()); // Duplicate key
        }

        // No users inserted
        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertTrue($this->uow->isScheduledForInsert($firstUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));

        self::assertTrue($this->uow->isScheduledForInsert($secondUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));

        self::assertTrue($this->uow->isScheduledForInsert($thirdUser));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($thirdUser));
    }

    public function testFatalInsertErrorWithEmbeddedDocument(): void
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
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(11000, $e->getCode());
        }

        self::assertSame(0, $collection->countDocuments());

        $this->assertTrue($this->uow->isScheduledForInsert($firstUser));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($firstUser));
        $this->assertTrue($this->uow->isScheduledForInsert($firstAddress));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($firstAddress));

        $this->assertTrue($this->uow->isScheduledForInsert($secondUser));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($secondUser));
        $this->assertTrue($this->uow->isScheduledForInsert($secondAddress));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($secondAddress));
    }

    public function testFatalUpsertError(): void
    {
        $user           = new ForumUser();
        $user->id       = new ObjectId(); // Specifying an identifier makes this an upsert
        $user->username = 'alcaeus';
        $this->uow->persist($user);

        $this->createFatalFailPoint('update');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        // No document was inserted
        self::assertSame(
            0,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertTrue($this->uow->isScheduledForUpsert($user));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testTransientUpsertError(): void
    {
        $user           = new ForumUser();
        $user->id       = new ObjectId(); // Specifying an identifier makes this an upsert
        $user->username = 'alcaeus';
        $this->uow->persist($user);

        $this->createTransientFailPoint('update');

        $this->uow->commit();

        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(),
        );

        self::assertFalse($this->uow->isScheduledForUpsert($user));
        self::assertEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testFatalUpdateError(): void
    {
        $user           = new ForumUser();
        $user->username = 'alcaeus';
        $this->uow->persist($user);
        $this->uow->commit();

        $user->username = 'jmikola';

        $this->createFatalFailPoint('update');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertTrue($this->uow->isScheduledForUpdate($user));
        self::assertNotEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testTransientUpdateError(): void
    {
        $user           = new ForumUser();
        $user->username = 'alcaeus';
        $this->uow->persist($user);
        $this->uow->commit();

        $user->username = 'jmikola';

        $this->createTransientFailPoint('update');

        $this->uow->commit();

        self::assertSame(
            1,
            $this->dm->getDocumentCollection(ForumUser::class)->countDocuments(['username' => 'jmikola']),
        );

        self::assertFalse($this->uow->isScheduledForUpdate($user));
        self::assertEquals([], $this->uow->getDocumentChangeSet($user));
    }

    public function testFatalUpdateErrorWithNewEmbeddedDocument(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');

        $this->uow->persist($user);
        $this->uow->commit();

        $address = new Address();
        $address->setCity('Olching');
        $user->setAddress($address);

        $this->createFatalFailPoint('update');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForInsert($address));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testTransientUpdateErrorWithNewEmbeddedDocument(): void
    {
        $user = new User();
        $user->setUsername('alcaeus');

        $this->uow->persist($user);
        $this->uow->commit();

        $address = new Address();
        $address->setCity('Olching');
        $user->setAddress($address);

        $this->createTransientFailPoint('update');

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForInsert($address));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testFatalUpdateErrorOfEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $address->setCity('Munich');

        $this->createFatalFailPoint('update');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForUpdate($address));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testTransientUpdateErrorOfEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $address->setCity('Munich');

        $this->createTransientFailPoint('update');

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForUpdate($address));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($address));
    }

    public function testFatalUpdateErrorWithRemovedEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $user->removeAddress();

        $this->createFatalFailPoint('update');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        $this->assertTrue($this->uow->isScheduledForUpdate($user));
        $this->assertNotEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertTrue($this->uow->isScheduledForDelete($address));

        // As $address is orphaned after changeset computation, it is removed from the identity map
        $this->assertFalse($this->uow->isInIdentityMap($address));
    }

    public function testTransientUpdateErrorWithRemovedEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $user->removeAddress();

        $this->createTransientFailPoint('update');

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($user));
        $this->assertEquals([], $this->uow->getDocumentChangeSet($user));
        $this->assertFalse($this->uow->isScheduledForDelete($address));
        $this->assertFalse($this->uow->isInIdentityMap($address));
    }

    public function testFatalDeleteErrorWithEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $this->uow->remove($user);

        $this->createFatalFailPoint('delete');

        try {
            $this->uow->commit();
            self::fail('Expected exception when committing');
        } catch (Throwable $e) {
            self::assertInstanceOf(BulkWriteException::class, $e);
            self::assertSame(192, $e->getCode());
        }

        // The document still exists, the deletion is still scheduled
        self::assertSame(
            1,
            $this->dm->getDocumentCollection(User::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertTrue($this->uow->isScheduledForDelete($user));
        self::assertTrue($this->uow->isScheduledForDelete($address));
    }

    public function testTransientDeleteErrorWithEmbeddedDocument(): void
    {
        $address = new Address();
        $address->setCity('Olching');

        $user = new User();
        $user->setUsername('alcaeus');
        $user->setAddress($address);

        $this->uow->persist($user);
        $this->uow->commit();

        $this->uow->remove($user);

        $this->createTransientFailPoint('delete');

        $this->uow->commit();

        self::assertSame(
            0,
            $this->dm->getDocumentCollection(User::class)->countDocuments(['username' => 'alcaeus']),
        );

        self::assertFalse($this->uow->isScheduledForDelete($address));
        self::assertFalse($this->uow->isScheduledForDelete($user));
    }

    /** Create a document manager with a single host to ensure failpoints target the correct server */
    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $client = new Client(self::getUri(false), [], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        return DocumentManager::create($client, $config);
    }

    protected static function getConfiguration(): Configuration
    {
        $configuration = parent::getConfiguration();
        $configuration->setUseTransactionalFlush(true);

        return $configuration;
    }

    private function createTransientFailPoint(string $failCommand): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            // Trigger the error twice, working around retryable writes
            'mode' => ['times' => 2],
            'data' => [
                'errorCode' => 192, // FailPointEnabled
                'errorLabels' => ['TransientTransactionError'],
                'failCommands' => [$failCommand],
            ],
        ]);
    }

    private function createFatalFailPoint(string $failCommand): void
    {
        $this->dm->getClient()->selectDatabase('admin')->command([
            'configureFailPoint' => 'failCommand',
            'mode' => ['times' => 1],
            'data' => [
                'errorCode' => 192, // FailPointEnabled
                'failCommands' => [$failCommand],
            ],
        ]);
    }
}
