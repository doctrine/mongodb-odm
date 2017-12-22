<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use Doctrine\ODM\MongoDB\Tools\DocumentRepositoryGenerator;

/**
 * Command to generate repository classes for mapping information.
 *
 * @since   1.0
 */
class GenerateRepositoriesCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:generate:repositories')
        ->setDescription('Generate repository classes from your mapping information.')
        ->setDefinition(array(
            new InputOption(
                'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
            new InputArgument(
                'dest-path', InputArgument::REQUIRED, 'The path to generate your repository classes.'
            )
        ))
        ->setHelp(<<<EOT
Generate repository classes from your mapping information.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();
        
        $metadatas = $dm->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Documents destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } elseif ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Documents destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            $numRepositories = 0;
            $generator = new DocumentRepositoryGenerator();

            foreach ($metadatas as $metadata) {
                if ($metadata->customRepositoryClassName) {
                    $output->write(
                        sprintf('Processing repository "<info>%s</info>"', $metadata->customRepositoryClassName) . PHP_EOL
                    );

                    $generator->writeDocumentRepositoryClass($metadata->customRepositoryClassName, $destPath);

                    $numRepositories++;
                }
            }

            if ($numRepositories) {
                // Outputting information message
                $output->write(PHP_EOL . sprintf('Repository classes generated to "<info>%s</INFO>"', $destPath) . PHP_EOL);
            } else {
                $output->write('No Repository classes were found to be processed.' . PHP_EOL);
            }
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}
