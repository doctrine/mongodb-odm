<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DropCommand extends AbstractCommand
{
    private $dropOrder = array(self::INDEX, self::COLLECTION, self::DB);

    protected function configure()
    {
        $this
            ->setName('odm:schema:drop')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Drop databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Drop collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Drop indexes')
            ->setDescription('Drop databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->dropOrder as $option) {
            if ($input->getOption($option)) {
                $drop[] = $option;
            }
        }

        // Default to the full drop order if no options were specified
        $drop = empty($drop) ? $this->dropOrder : $drop;

        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();

        foreach ($drop as $option) {
            try {
                if (isset($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class);
                } else {
                    $this->{'process' . ucfirst($option)}($sm);
                }
                $output->writeln(sprintf(
                    'Dropped <comment>%s%s</comment> for <info>%s</info>',
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
        $sm->dropDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->dropCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        $sm->dropDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->dropDatabases();
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->deleteDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->deleteIndexes();
    }
}
