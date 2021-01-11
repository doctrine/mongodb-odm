<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache;

use Doctrine\Common\Cache\ApcCache;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use const PHP_EOL;

/**
 * Command to clear the metadata cache of the various cache drivers.
 */
class MetadataCommand extends Command
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:clear-cache:metadata')
        ->setDescription('Clear all metadata cache of the various cache drivers.')
        ->setDefinition([])
        ->setHelp(<<<EOT
Clear all metadata cache of the various cache drivers.
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm          = $this->getHelper('documentManager')->getDocumentManager();
        $cacheDriver = $dm->getConfiguration()->getMetadataCacheImpl();

        if (! $cacheDriver) {
            throw new InvalidArgumentException('No Metadata cache driver is configured on given DocumentManager.');
        }

        if ($cacheDriver instanceof ApcCache) {
            throw new LogicException('Cannot clear APC Cache from Console, its shared in the Webserver memory and not accessible from the CLI.');
        }

        $output->write('Clearing ALL Metadata cache entries' . PHP_EOL);

        $success = $cacheDriver->deleteAll();

        if ($success) {
            $output->write('The cache entries were successfully deleted.' . PHP_EOL);
        } else {
            $output->write('No entries to be deleted.' . PHP_EOL);
        }

        return 0;
    }
}
