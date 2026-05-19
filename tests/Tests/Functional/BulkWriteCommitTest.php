<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\ForumUser;
use Documents\Phonebook;
use Documents\Phonenumber;
use Documents\User;

use function count;

/**
 * Asserts that DocumentPersister / CollectionPersister consolidate every
 * write against a single MongoDB collection into one `bulkWrite` command
 * per commit phase.
 */
final class BulkWriteCommitTest extends BaseTestCase
{
    // The command logger counts every command, so transactional flush (which
    // wraps each commit with startTransaction/commitTransaction) would skew the
    // numbers. Test the per-collection consolidation in the non-transactional
    // flow so the assertions remain deterministic.
    protected static bool $allowsTransactions = false;

    private CommandLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown(): void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testManyInsertsIntoOneCollectionEmitOneBulkWrite(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $user           = new ForumUser();
            $user->username = 'user-' . $i;
            $this->dm->persist($user);
        }

        $this->logger->clear();
        $this->dm->flush();

        self::assertSame(
            1,
            $this->countCommands('insert'),
            'Five ForumUser inserts should consolidate into one bulkWrite (which the server logs as a single "insert" command).',
        );
    }

    public function testInsertsAcrossTwoCollectionsEmitOneCommandEach(): void
    {
        $user           = new ForumUser();
        $user->username = 'forum';

        $other = new User();
        $other->setUsername('docs');

        $this->dm->persist($user);
        $this->dm->persist($other);

        $this->logger->clear();
        $this->dm->flush();

        // Two different collections → two bulkWrites → two server commands.
        self::assertSame(2, $this->countCommands('insert'));
    }

    public function testUpdatesAcrossTwoCollectionsEmitOneCommandEach(): void
    {
        $userA = new User();
        $userA->setUsername('a');
        $forumUser           = new ForumUser();
        $forumUser->username = 'b';
        $this->dm->persist($userA);
        $this->dm->persist($forumUser);
        $this->dm->flush();

        $userA->setUsername('a2');
        $forumUser->username = 'b2';

        $this->logger->clear();
        $this->dm->flush();

        self::assertSame(2, $this->countCommands('update'));
    }

    public function testMixedInsertUpdateOnSameCollectionIsOneBulkWritePerPhase(): void
    {
        $existing           = new ForumUser();
        $existing->username = 'existing';
        $this->dm->persist($existing);
        $this->dm->flush();

        $existing->username = 'existing-modified';

        $new           = new ForumUser();
        $new->username = 'new';
        $this->dm->persist($new);

        $this->logger->clear();
        $this->dm->flush();

        // Insert phase and update phase each drain once → 1 insert + 1 update command.
        self::assertSame(1, $this->countCommands('insert'));
        self::assertSame(1, $this->countCommands('update'));
    }

    public function testInsertWithEmbeddedCollectionsIsOneCommand(): void
    {
        $user = new User();
        $user->setUsername('embed-host');

        $book = new Phonebook('Private');
        $book->addPhonenumber(new Phonenumber('11111111'));
        $book->addPhonenumber(new Phonenumber('22222222'));
        $user->addPhonebook($book);

        $this->dm->persist($user);
        $this->logger->clear();
        $this->dm->flush();

        // The embedded phonebooks/phonenumbers are inlined into the parent insert,
        // so a single bulkWrite to the User collection should suffice. The post-insert
        // addToSet pass for the phonebook does not target a separate collection.
        self::assertSame(1, count($this->logger));
    }

    public function testParentUpdateAndCollectionPushOnSameDocumentIsOneCommand(): void
    {
        $user = new User();
        $user->setUsername('with-book');
        $book = new Phonebook('Private');
        $book->addPhonenumber(new Phonenumber('11111111'));
        $user->addPhonebook($book);
        $this->dm->persist($user);
        $this->dm->flush();

        // Mutate both the parent and the embedded collection in one go.
        $user->setUsername('with-book-modified');
        $book->addPhonenumber(new Phonenumber('22222222'));

        $this->logger->clear();
        $this->dm->flush();

        // One bulkWrite to the User collection that carries the parent update
        // op and the collection-level $push op.
        self::assertSame(1, $this->countCommands('update'));
    }

    public function testDeletesAcrossOneCollectionEmitOneCommand(): void
    {
        $a           = new ForumUser();
        $a->username = 'a';
        $b           = new ForumUser();
        $b->username = 'b';
        $this->dm->persist($a);
        $this->dm->persist($b);
        $this->dm->flush();

        $this->dm->remove($a);
        $this->dm->remove($b);

        $this->logger->clear();
        $this->dm->flush();

        self::assertSame(1, $this->countCommands('delete'));
    }

    private function countCommands(string $name): int
    {
        $count = 0;
        foreach ($this->logger->getAll() as $event) {
            if ($event->getCommandName() !== $name) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}
