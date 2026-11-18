<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Server;
use MongoDB\Model\DatabaseInfo;

use function array_key_exists;
use function array_map;
use function in_array;
use function iterator_to_array;
use function preg_match;
use function version_compare;

use const DOCTRINE_MONGODB_DATABASE;

/**
 * Base class for tests that talk to a MongoDB server.
 *
 * Unit tests that never issue a command belong on UnitTestCase instead, so they
 * can run without a server.
 */
abstract class BaseTestCase extends UnitTestCase
{
    protected static ?bool $supportsTransactions;
    protected static bool $allowsTransactions = true;
    private bool $disableFailPoints           = false;

    protected static function shouldUseTransactionalFlush(): bool
    {
        // Transactional flush only makes sense against a topology that supports
        // transactions, which can only be determined by asking the server.
        return static::$allowsTransactions && self::supportsTransactions();
    }

    protected function tearDown(): void
    {
        if (! $this->dm) {
            return;
        }

        $client = $this->dm->getClient();

        // Remove any fail points that may have been set
        if ($this->disableFailPoints) {
            $client->getDatabase('admin')->command([
                'configureFailPoint' => 'failCommand',
                'mode' => 'off',
            ]);
            $this->disableFailPoints = false;
        }

        // Check if the database exists. Calling listCollections on a non-existing
        // database in a sharded setup will cause an invalid command cursor to be
        // returned
        $databases     = iterator_to_array($client->listDatabases());
        $databaseNames = array_map(static fn (DatabaseInfo $database) => $database->getName(), $databases);
        if (! in_array(DOCTRINE_MONGODB_DATABASE, $databaseNames)) {
            return;
        }

        $collections = $client->getDatabase(DOCTRINE_MONGODB_DATABASE)->listCollections();

        foreach ($collections as $collection) {
            // See https://jira.mongodb.org/browse/SERVER-16541
            if (preg_match('#^system\.#', $collection->getName())) {
                continue;
            }

            $client->getCollection(DOCTRINE_MONGODB_DATABASE, $collection->getName())->drop();
        }
    }

    protected function getServerVersion(): string
    {
        $result = $this->dm->getClient()->getDatabase(DOCTRINE_MONGODB_DATABASE)->command(['buildInfo' => 1], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

        return $result['version'];
    }

    protected function getPrimaryServer(): Server
    {
        return $this->dm->getClient()->getManager()->selectServer();
    }

    protected function skipTestIfNoTransactionSupport(): void
    {
        if (! self::supportsTransactions()) {
            $this->markTestSkipped('Test requires a topology that supports transactions');
        }
    }

    protected function skipTestIfTransactionalFlushDisabled(): void
    {
        if (! $this->dm?->getConfiguration()->isTransactionalFlushEnabled()) {
            $this->markTestSkipped('Test only applies when transactional flush is enabled');
        }
    }

    protected function skipTestIfTransactionalFlushEnabled(): void
    {
        if ($this->dm?->getConfiguration()->isTransactionalFlushEnabled()) {
            $this->markTestSkipped('Test is not compatible with transactional flush');
        }
    }

    /** @param class-string $className */
    protected function skipTestIfNotSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

        if (array_key_exists('shardCollection', $result['commands'])) {
            return;
        }

        $this->markTestSkipped('Test skipped because server does not support sharding');
    }

    /** @param class-string $className */
    protected function skipTestIfSharded(string $className): void
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true], ['typeMap' => DocumentManager::CLIENT_TYPEMAP])->toArray()[0];

        if (! array_key_exists('shardCollection', $result['commands'])) {
            return;
        }

        $this->markTestSkipped('Test does not apply on sharded clusters');
    }

    protected function skipTestIfQueryableEncryptionNotSupported(): void
    {
        if ($this->getPrimaryServer()->getType() === Server::TYPE_STANDALONE) {
            $this->markTestSkipped('Queryable Encryption test requires a replica set or sharded cluster');
        }

        $buildInfo = $this->getPrimaryServer()->executeCommand(
            DOCTRINE_MONGODB_DATABASE,
            new Command(['buildInfo' => 1]),
        )->toArray()[0];

        if (! in_array('enterprise', $buildInfo->modules ?? [])) {
            $this->markTestSkipped('Queryable Encryption test requires MongoDB Atlas or Enterprise');
        }

        $this->requireVersion($buildInfo->version, '7.0', '<', 'Queryable Encryption test requires MongoDB 7.0 or higher');
    }

    protected function requireVersion(string $installedVersion, string $requiredVersion, ?string $operator, string $message): void
    {
        if (! version_compare($installedVersion, $requiredVersion, $operator)) {
            return;
        }

        $this->markTestSkipped($message);
    }

    protected function skipOnMongoDB42(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '4.2.0', '>=', $message);
    }

    protected function requireMongoDB42(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '4.2.0', '<', $message);
    }

    protected function requireMongoDB63(string $message): void
    {
        $this->requireVersion($this->getServerVersion(), '6.3.0', '<', $message);
    }

    protected static function supportsTransactions(): bool
    {
        return self::$supportsTransactions ??= self::detectTransactionSupport();
    }

    private static function detectTransactionSupport(): bool
    {
        $manager = new Manager(self::getUri());

        return $manager->selectServer()->getType() !== Server::TYPE_STANDALONE;
    }

    /** @param list<string>|null $errorLabels */
    protected function createFailPoint(
        string $failCommand,
        bool $transient = false,
        int $times = 1,
        int $errorCode = 192, // FailPointEnabled
        ?array $errorLabels = null,
    ): void {
        try {
            $this->dm->getClient()->getManager()->executeCommand(
                'admin',
                new Command([
                    'configureFailPoint' => 'failCommand',
                    'mode'               => ['times' => $times],
                    'data' => [
                        'errorCode' => $errorCode,
                        'errorLabels' => $errorLabels ?? ($transient ? ['TransientTransactionError'] : []),
                        'failCommands' => [$failCommand],
                    ],
                ]),
            );
            $this->disableFailPoints = true;
        } catch (CommandException $exception) {
            // no such command: 'configureFailPoint'
            if ($exception->getCode() === 59) {
                self::markTestSkipped('Test skipped because the server does not support fail points');
            }
        }
    }
}
