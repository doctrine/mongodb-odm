<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function sprintf;
use function ucfirst;

class DropCommand extends AbstractCommand
{
    /** @var string[] */
    private $dropOrder = [self::INDEX, self::COLLECTION, self::DB];

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
        $drop = array_filter($this->dropOrder, function ($option) use ($input) {
            return $input->getOption($option);
        });

        // Default to the full drop order if no options were specified
        $drop = empty($drop) ? $this->dropOrder : $drop;

        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();
        $isErrored = false;

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
