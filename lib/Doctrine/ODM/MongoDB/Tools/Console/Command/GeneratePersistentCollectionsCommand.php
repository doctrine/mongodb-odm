<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
 * Command to (re)generate the persistent collection classes used by doctrine.
 */
class GeneratePersistentCollectionsCommand extends Command
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        $this
            ->setName('odm:generate:persistent-collections')
            ->setDescription('Generates persistent collection classes for custom collections.')
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
                    'The path to generate your proxy classes. If none is provided, it will attempt to grab from configuration.'
                ),
            ])
            ->setHelp(<<<'EOT'
Generates persistent collection classes for custom collections.
EOT
            );
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $filter = $input->getOption('filter');
        assert(is_array($filter));

        $dm = $this->getHelper('documentManager')->getDocumentManager();

        $metadatas = $dm->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $filter);
        $destPath  = $input->getArgument('dest-path');

        // Process destination directory
        if ($destPath === null) {
            $destPath = $dm->getConfiguration()->getPersistentCollectionDir();
        }

        if (! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);
        assert($destPath !== false);

        if (! file_exists($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Persistent collections destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        }

        if (! is_writable($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Persistent collections destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            $generated           = [];
            $collectionGenerator = $dm->getConfiguration()->getPersistentCollectionGenerator();
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
                foreach ($metadata->getAssociationNames() as $fieldName) {
                    $mapping = $metadata->getFieldMapping($fieldName);
                    if (empty($mapping['collectionClass']) || isset($generated[$mapping['collectionClass']])) {
                        continue;
                    }

                    $generated[$mapping['collectionClass']] = true;
                    $output->write(
                        sprintf('Generating class for "<info>%s</info>"', $mapping['collectionClass']) . PHP_EOL
                    );
                    $collectionGenerator->generateClass($mapping['collectionClass'], $destPath);
                }
            }

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Persistent collections classes generated to "<info>%s</info>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }

        return 0;
    }
}
