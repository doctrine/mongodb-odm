<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Symfony\Components\Console\Command\Command;
use Symfony\Components\Console\Input;
use Symfony\Components\Console\Output;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SchemaCreateCommand extends Command
{
    const DBS         = 'db';
    const COLLECTIONS = 'collection';

    protected $avaialbleObjects = array(self::COLLECTIONS, self::DBS);

    protected function configure()
    {
        $this
            ->setName('odm:mongodb:schema:create')
            ->setDescription('Allows to create databases and/or collections for your documents')
            ->setDefinition(array(
                new Input\InputOption('class', 'c', Input\InputOption::PARAMETER_OPTIONAL, 'the class name to create "db" or "collection" for, all classes will be used if none specified', null),
                new Input\InputArgument('targets', Input\InputArgument::IS_ARRAY, 'An array for ["db", "collection"] or either option', null),
            ))
        ;
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $toCreate = (array) $input->getArgument('targets');
        $classes = $input->getOption('class');
        foreach ($toCreate as $object) {
            if ( !\in_array($object, $this->avaialbleObjects)) {
                throw new \InvalidArgumentException('Undefined object "' . $object . '", only "' . implode('", "', $this->avaialbleObjects) . '" are allowed.');
            }
            try {
                $this->{'create' . ucfirst($object)}($classes);
                $output->writeln('<info>Successfully created "' . $object . '"' . (isset($class) ? ' for ' . $class : 's for all classes') . '</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }

    protected function createDb($class = null)
    {
        $sm = $this->getSchemaManager();
        if (isset($class)) {
            $sm->createDocumentDatabase($class);
        } else {
            $sm->createDatabases();
        }
    }

    protected function createCollection($class = null)
    {
        $sm = $this->getSchemaManager();
        if (isset($class)) {
            $sm->createDocumentCollection($class);
        } else {
            $sm->createCollections();
        }
    }

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