<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use InvalidArgumentException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function assert;
use function count;
use function file_exists;
use function is_array;
use function is_dir;
use function is_writable;
use function mkdir;
use function realpath;
use function sprintf;

use const PHP_EOL;

/**
 * Command to (re)generate the hydrator classes used by doctrine.
 */
class GenerateHydratorsCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:generate:hydrators')
        ->setDescription('Generates hydrator classes for document classes.')
        ->setDefinition([
            new InputOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
            new InputArgument(
                'dest-path',
                InputArgument::OPTIONAL,
                'The path to generate your hydrator classes. If none is provided, it will attempt to grab from configuration.'
            ),
        ])
        ->setHelp(<<<EOT
Generates hydrator classes for document classes.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $filter = $input->getOption('filter');
        assert(is_array($filter));

        $dm = $this->getHelper('documentManager')->getDocumentManager();

        $metadatas = $dm->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $filter);
        $destPath  = $input->getArgument('dest-path');

        // Process destination directory
        if ($destPath === null) {
            $destPath = $dm->getConfiguration()->getHydratorDir();
        }

        if (! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);
        assert($destPath !== false);

        if (! file_exists($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Hydrators destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        }

        if (! is_writable($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Hydrators destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
            }

            // Generating Hydrators
            $dm->getHydratorFactory()->generateHydratorClasses($metadatas, $destPath);

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Hydrator classes generated to "<info>%s</info>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }

        return 0;
    }
}
