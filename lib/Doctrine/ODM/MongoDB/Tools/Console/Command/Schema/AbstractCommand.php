<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function is_numeric;
use function is_string;

abstract class AbstractCommand extends Command
{
    public const DB           = 'db';
    public const COLLECTION   = 'collection';
    public const INDEX        = 'index';
    public const SEARCH_INDEX = 'search-index';

    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('maxTimeMs', null, InputOption::VALUE_REQUIRED, 'An optional maxTimeMs that will be used for all schema operations.')
            ->addOption('w', null, InputOption::VALUE_REQUIRED, 'An optional w option for the write concern that will be used for all schema operations.')
            ->addOption('wTimeout', null, InputOption::VALUE_REQUIRED, 'An optional wTimeout option for the write concern that will be used for all schema operations. Using this option without a w option will cause an exception to be thrown.')
            ->addOption('journal', null, InputOption::VALUE_REQUIRED, 'An optional journal option for the write concern that will be used for all schema operations. Using this option without a w option will cause an exception to be thrown.');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support collections');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support collections');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support databases');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDb(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support databases');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support indexes');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support indexes');
    }

    /** @throws BadMethodCallException */
    protected function processSearchIndex(SchemaManager $sm): void
    {
        throw new BadMethodCallException('This command does not support search indexes');
    }

    /**
     * @param class-string $document
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentSearchIndex(SchemaManager $sm, string $document): void
    {
        throw new BadMethodCallException('This command does not support search indexes');
    }

    /** @return SchemaManager */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /** @return DocumentManager */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /** @return ClassMetadataFactoryInterface */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }

    protected function getMaxTimeMsFromInput(InputInterface $input): ?int
    {
        $maxTimeMs = $input->getOption('maxTimeMs');

        return is_string($maxTimeMs) ? (int) $maxTimeMs : null;
    }

    protected function getWriteConcernFromInput(InputInterface $input): ?WriteConcern
    {
        $w        = $input->getOption('w');
        $wTimeout = $input->getOption('wTimeout');
        $journal  = $input->getOption('journal');

        if (! is_string($w)) {
            if ($wTimeout !== null || $journal !== null) {
                throw new InvalidOptionException('The "wTimeout" or "journal" options can only be used when passing a "w" option.');
            }

            return null;
        }

        if (is_numeric($w)) {
            $w = (int) $w;
        }

        $wTimeout = is_string($wTimeout) ? (int) $wTimeout : 0;
        $journal  = is_string($journal) ? (bool) $journal : false;

        return new WriteConcern($w, $wTimeout, $journal);
    }
}
