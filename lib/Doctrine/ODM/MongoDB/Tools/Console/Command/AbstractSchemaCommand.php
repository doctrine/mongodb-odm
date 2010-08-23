<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Doctrine\ODM\MongoDB\SchemaManager;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
abstract class AbstractSchemaCommand extends Command
{
    const DBS = 'DB';
    const COLLECTIONS = 'Collection';

    protected $avaialbleObjects = array(self::COLLECTIONS, self::DBS);

    protected $_commandName;

    protected function configure()
    {
        $this
            ->setName('odm:mongodb:schema:' . $this->_commandName)
            ->setDescription('Allows to create databases and/or collections for your documents')
            ->setDefinition(array(
                new Input\InputOption('class', 'c', Input\InputOption::PARAMETER_OPTIONAL, 'the class name to create "db" or "collection" for, all classes will be used if none specified', null),
                new Input\InputArgument('targets', Input\InputArgument::IS_ARRAY, 'An array for ["' . implode('", "', $this->avaialbleObjects) . '"] or either option', null),
            ))
        ;
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $toCreate = (array) $input->getArgument('targets');
        $class = $input->getOption('class');
        foreach ($toCreate as $object) {
            if (!in_array($object, $this->avaialbleObjects)) {
                throw new \InvalidArgumentException('Undefined object "' . $object . '", only "' . implode('", "', $this->avaialbleObjects) . '" are allowed.');
            }
            try {
                $sm = $this->getSchemaManager();
                if (isset($class)) {
                    $this->{'processDocument' . $object}($sm, $class);
                } else {
                    $this->{'process' . $object . 's'}($sm);
                }
                $output->writeln('<info>Successfully processed "' . $object . '"' . (isset($class) ? ' for ' . $class : 's for all classes') . '</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }

    abstract protected function processDocumentCollection(SchemaManager $sm, $document);
    abstract protected function processCollections(SchemaManager $sm);
    abstract protected function processDocumentDB(SchemaManager $sm, $document);
    abstract protected function processDBs(SchemaManager $sm);

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