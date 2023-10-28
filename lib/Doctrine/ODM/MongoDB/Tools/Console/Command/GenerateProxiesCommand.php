<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\ConfigurationException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function assert;
use function count;
use function file_exists;
use function is_array;
use function is_dir;
use function is_string;
use function is_writable;
use function mkdir;
use function realpath;
use function sprintf;

use const PHP_EOL;

/**
 * Command to (re)generate the proxy classes used by doctrine.
 */
class GenerateProxiesCommand extends Command
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        $this
        ->setName('odm:generate:proxies')
        ->setDescription('Generates proxy classes for document classes.')
        ->setDefinition([
            new InputOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
        ])
        ->setHelp(<<<'EOT'
Generates proxy classes for document classes.
EOT
        );
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $filter = $input->getOption('filter');
        assert(is_array($filter));

        $dm = $this->getHelper('documentManager')->getDocumentManager();
        assert($dm instanceof DocumentManager);

        $metadatas = array_filter($dm->getMetadataFactory()->getAllMetadata(), static fn (ClassMetadata $classMetadata): bool => ! $classMetadata->isEmbeddedDocument && ! $classMetadata->isMappedSuperclass && ! $classMetadata->isQueryResultDocument);
        $metadatas = MetadataFilter::filter($metadatas, $filter);
        $destPath  = $dm->getConfiguration()->getProxyDir();

        if (! is_string($destPath)) {
            throw ConfigurationException::proxyDirMissing();
        }

        if (! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);
        assert($destPath !== false);

        if (! file_exists($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        }

        if (! is_writable($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
            }

            // Generating Proxies
            $dm->getProxyFactory()->generateProxyClasses($metadatas);

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Proxy classes generated to "<info>%s</info>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }

        return 0;
    }
}
