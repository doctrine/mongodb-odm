<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tools\Console\Command\CommandCompatibility;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;

use const PHP_EOL;

/**
 * Command to clear the metadata cache of the various cache drivers.
 */
class MetadataCommand extends Command
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        $this
        ->setName('odm:clear-cache:metadata')
        ->setDescription('Clear all metadata cache of the various cache drivers.')
        ->setDefinition([])
        ->setHelp(<<<'EOT'
Clear all metadata cache of the various cache drivers.
EOT
        );
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();
        assert($dm instanceof DocumentManager);

        $cacheDriver = $dm->getConfiguration()->getMetadataCache();

        if (! $cacheDriver) {
            throw new InvalidArgumentException('No Metadata cache driver is configured on given DocumentManager.');
        }

        $output->write('Clearing ALL Metadata cache entries' . PHP_EOL);

        $success = $cacheDriver->clear();

        if ($success) {
            $output->write('The cache entries were successfully deleted.' . PHP_EOL);
        } else {
            $output->write('No entries to be deleted.' . PHP_EOL);
        }

        return 0;
    }
}
