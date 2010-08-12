<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Symfony\Components\Console\Command\Command;
use Symfony\Components\Console\Input;
use Symfony\Components\Console\Output;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class IndexesCommand extends Command
{
    const CREATE  = 'ensure';
    const DROP    = 'delete';
    const REPLACE = 'replace';

    protected function configure()
    {
        $this
            ->setName('odm:mongodb:indexes')
            ->setDescription('Ensure all indexes for a document class')
            ->setDefinition(array(
                new Input\InputOption('mode', 'm', Input\InputOption::PARAMETER_REQUIRED, 'allows to \'' . self::CREATE . '\', \'' . self::DROP . '\', \'' . self::REPLACE . '\' all indexes for a document', self::CREATE),
                new Input\InputArgument('class', Input\InputArgument::REQUIRED, 'name of the class to ensure indexes for'),
            ))
        ;
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $dm = $this->getDocumentManager();
        $className = $input->getArgument('class');
        $mode = $input->getOption('mode');
        $replace = false;
        $message = null;
        switch ($mode) {
            case self::REPLACE:
                $replace = true;
                $message = $message ?: 'Sucessfully replaced indexes for ' . $className;
            case self::DROP:
                $message = $message ?: 'Sucessfully deleted indexes for ' . $className;
                $dm->deleteDocumentIndexes($className);
                if ( ! $replace) {
                    break;
                }
            case self::CREATE:
                $message = $message ?: 'Sucessfully ensured indexes for ' . $className;
                $dm->ensureDocumentIndexes($className);
                break;
            default:
                throw new \InvalidArgumentException('Option \'mode\' must be one of \'' . self::CREATE . '\', \'' . self::DROP . '\' or \'' . self::REPLACE . '\'. \'' . $mode . '\' given.');
        }
        $output->write('<info>' . $message . '</info>');
    }
    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManager() {
        return $this->getHelper('documentManager')->getDocumentManager();
    }
}