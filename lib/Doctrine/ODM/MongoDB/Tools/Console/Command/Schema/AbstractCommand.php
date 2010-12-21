<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Doctrine\ODM\MongoDB\SchemaManager;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const DBS = 'db';
    const COLLECTIONS = 'collection';
    const INDEXES = 'index';
    const PROXIES = 'proxy';

    /**
     * Dropping and Replacement of schema happens in reverse order (indexes <- collections <- dbs)
     *
     * @var array
     */
    protected $availableOptions = array(self::INDEXES, self::COLLECTIONS, self::DBS, self::PROXIES);

    protected $_commandName;

    protected function configure()
    {
        $this
            ->setName('odm:schema:' . $this->_commandName)
            ->setDescription("Allows to $this->_commandName databases and/or collections for your documents")
            ->setDefinition(array(
                new Input\InputOption('class', 'c', Input\InputOption::VALUE_OPTIONAL, 'the class name to create "db" or "collection" for, all classes will be used if none specified', null),
                new Input\InputOption(self::DBS, null, Input\InputOption::VALUE_NONE, ''),
                new Input\InputOption(self::COLLECTIONS, null, Input\InputOption::VALUE_NONE, ''),
                new Input\InputOption(self::INDEXES, null, Input\InputOption::VALUE_NONE, ''),
                new Input\InputOption(self::PROXIES, null, Input\InputOption::VALUE_NONE, ''),
            ))
        ;
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();
        foreach ($this->availableOptions as $option) {
            if (false !== $input->getOption($option)) {
                try {
                    if (isset($class)) {
                        $this->{'processDocument' . ucfirst($option)}($sm, $class);
                    } else {
                        $this->{'process' . ucfirst($option)}($sm);
                    }
                    $output->writeln('<info>Successfully processed "' . $option . '" for ' . (isset($class) ? $class : 'all classes') . '</info>');
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }
        }
    }

    abstract protected function processDocumentCollection(SchemaManager $sm, $document);
    abstract protected function processCollection(SchemaManager $sm);
    abstract protected function processDocumentDb(SchemaManager $sm, $document);
    abstract protected function processDb(SchemaManager $sm);
    abstract protected function processDocumentIndex(SchemaManager $sm, $document);
    abstract protected function processIndex(SchemaManager $sm);
    abstract protected function processDocumentProxy(SchemaManager $sm, $document);
    abstract protected function processProxy(SchemaManager $sm);

    /**
     * @return Doctrine\ODM\MongoDB\SchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /**
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }

}
