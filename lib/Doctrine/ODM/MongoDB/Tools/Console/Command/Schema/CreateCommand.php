<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class CreateCommand extends AbstractCommand
{
    private $createOrder = array(self::DB, self::COLLECTION, self::INDEX);

    protected function configure()
    {
        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Create databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->setDescription('Create databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->createOrder as $option) {
            if ($input->getOption($option)) {
                $create[] = $option;
            }
        }

        // Default to the full creation order if no options were specified
        $create = empty($create) ? $this->createOrder : $create;

        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();

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
                    (isset($class) ? $class : 'all classes')
                ));
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
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
        $sm->ensureDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureIndexes();
    }

    protected function processDocumentProxy(SchemaManager $sm, $document)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses(array($this->getMetadataFactory()->getMetadataFor($document)));
    }

    protected function processProxy(SchemaManager $sm)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($this->getMetadataFactory()->getAllMetadata());
    }
}
