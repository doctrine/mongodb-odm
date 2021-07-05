<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use LogicException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use function assert;
use function is_numeric;
use function is_string;
use function json_decode;

/**
 * Command to query mongodb and inspect the outputted results from your document classes.
 */
class QueryCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('odm:query')
        ->setDescription('Query mongodb and inspect the outputted results from your document classes.')
        ->setDefinition([
            new InputArgument(
                'class',
                InputArgument::REQUIRED,
                'The class to query.'
            ),
            new InputArgument(
                'query',
                InputArgument::REQUIRED,
                'The query to execute and output the results for.'
            ),
            new InputOption(
                'hydrate',
                null,
                InputOption::VALUE_NONE,
                'Whether or not to hydrate the results in to document objects.'
            ),
            new InputOption(
                'skip',
                null,
                InputOption::VALUE_REQUIRED,
                'The number of documents to skip in the cursor.'
            ),
            new InputOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'The number of documents to return.'
            ),
        ])
        ->setHelp(<<<EOT
Execute a query and output the results.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $query = $input->getArgument('query');
        assert(is_string($query));

        $dm = $this->getHelper('documentManager')->getDocumentManager();
        $qb = $dm->getRepository($input->getArgument('class'))->createQueryBuilder();
        $qb->setQueryArray((array) json_decode($query));
        $qb->hydrate((bool) $input->getOption('hydrate'));

        $skip = $input->getOption('skip');
        if ($skip !== null) {
            if (! is_numeric($skip)) {
                throw new LogicException("Option 'skip' must contain an integer value");
            }

            $qb->skip((int) $skip);
        }

        $limit = $input->getOption('limit');
        if ($limit !== null) {
            if (! is_numeric($limit)) {
                throw new LogicException("Option 'limit' must contain an integer value");
            }

            $qb->limit((int) $limit);
        }

        $cloner = new VarCloner();
        $dumper = new CliDumper(static function (string $payload) use ($output): void {
            $output->write($payload);
        });

        foreach ($qb->getQuery() as $result) {
            $dumper->dump($cloner->cloneVar($result));
        }

        return 0;
    }
}
