<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand
{
    private $createOrder = array(self::DB, self::COLLECTION, self::INDEX);

    private $timeout;

    protected function configure()
    {
        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout (ms) for acknowledged index creation')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Create databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->setDescription('Create databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::DB)) {
            @trigger_error('The ' . self::DB . ' option is deprecated and will be removed in ODM 2.0', E_USER_DEPRECATED);
        }

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
                    (isset($class) ? (self::INDEX === $option ? '(es)' : '') : (self::INDEX === $option ? 'es' : 's')),
                    ($class ?? 'all classes')
                ));
            } catch (\Exception $e) {
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
        $sm->createDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->createDatabases();
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

        if (!$classMetadata->isEmbeddedDocument && !$classMetadata->isMappedSuperclass && !$classMetadata->isQueryResultDocument) {
            $this->getDocumentManager()->getProxyFactory()->generateProxyClasses(array($classMetadata));
        }
    }

    protected function processProxy(SchemaManager $sm)
    {
        $classes = array_filter($this->getMetadataFactory()->getAllMetadata(), function (ClassMetadata $classMetadata) {
            return !$classMetadata->isEmbeddedDocument && !$classMetadata->isMappedSuperclass && !$classMetadata->isQueryResultDocument;
        });

        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($classes);
    }
}
