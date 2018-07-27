<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function sprintf;
use function ucfirst;

class CreateCommand extends AbstractCommand
{
    /** @var string[] */
    private $createOrder = [self::COLLECTION, self::INDEX];

    /** @var int|null */
    private $timeout;

    protected function configure()
    {
        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout (ms) for acknowledged index creation')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->setDescription('Create databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $create = array_filter($this->createOrder, function ($option) use ($input) {
            return $input->getOption($option);
        });

        // Default to the full creation order if no options were specified
        $create = empty($create) ? $this->createOrder : $create;

        $class = $input->getOption('class');

        $timeout = $input->getOption('timeout');
        $this->timeout = isset($timeout) ? (int) $timeout : null;

        $sm = $this->getSchemaManager();
        $isErrored = false;

        foreach ($create as $option) {
            try {
                if (isset($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class);
                } else {
                    $this->{'process' . ucfirst($option)}($sm);
                }
                $output->writeln(sprintf(
                    'Created <comment>%s%s</comment> for <info>%s</info>',
                    $option,
                    (isset($class) ? ($option === self::INDEX ? '(es)' : '') : ($option === self::INDEX ? 'es' : 's')),
                    $class ?? 'all classes'
                ));
            } catch (\Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $isErrored = true;
            }
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        $sm->createDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->createCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('A database is created automatically by MongoDB (>= 3.0).');
    }

    protected function processDb(SchemaManager $sm)
    {
        throw new \BadMethodCallException('A database is created automatically by MongoDB (>= 3.0).');
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->ensureDocumentIndexes($document, $this->timeout);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureIndexes($this->timeout);
    }

    protected function processDocumentProxy(SchemaManager $sm, $document)
    {
        $classMetadata = $this->getMetadataFactory()->getMetadataFor($document);

        if ($classMetadata->isEmbeddedDocument || $classMetadata->isMappedSuperclass || $classMetadata->isQueryResultDocument) {
            return;
        }

        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses([$classMetadata]);
    }

    protected function processProxy(SchemaManager $sm)
    {
        $classes = array_filter($this->getMetadataFactory()->getAllMetadata(), function (ClassMetadata $classMetadata) {
            return ! $classMetadata->isEmbeddedDocument && ! $classMetadata->isMappedSuperclass && ! $classMetadata->isQueryResultDocument;
        });

        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($classes);
    }
}
