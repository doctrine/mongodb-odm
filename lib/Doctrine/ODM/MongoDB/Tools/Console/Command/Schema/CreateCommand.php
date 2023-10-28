<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\Tools\Console\Command\CommandCompatibility;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_filter;
use function is_string;
use function sprintf;
use function ucfirst;

class CreateCommand extends AbstractCommand
{
    use CommandCompatibility;

    /** @var string[] */
    private array $createOrder = [self::COLLECTION, self::INDEX];

    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->addOption('background', null, InputOption::VALUE_NONE, sprintf('Create indexes in background (requires "%s" option)', self::INDEX))
            ->setDescription('Create databases, collections and indexes for your documents');
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $create = array_filter($this->createOrder, static fn (string $option): bool => (bool) $input->getOption($option));

        // Default to the full creation order if no options were specified
        $create = empty($create) ? $this->createOrder : $create;

        $class      = $input->getOption('class');
        $background = (bool) $input->getOption('background');

        $sm        = $this->getSchemaManager();
        $isErrored = false;

        foreach ($create as $option) {
            try {
                if (isset($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input), $background);
                } else {
                    $this->{'process' . ucfirst($option)}($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input), $background);
                }

                $output->writeln(sprintf(
                    'Created <comment>%s%s</comment> for <info>%s</info>',
                    $option,
                    is_string($class) ? ($option === self::INDEX ? '(es)' : '') : ($option === self::INDEX ? 'es' : 's'),
                    is_string($class) ? $class : 'all classes'
                ));
            } catch (Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $isErrored = true;
            }
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->createDocumentCollection($document, $maxTimeMs, $writeConcern);
    }

    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->createCollections($maxTimeMs, $writeConcern);
    }

    protected function processDocumentDb(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('A database is created automatically by MongoDB (>= 3.0).');
    }

    protected function processDb(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('A database is created automatically by MongoDB (>= 3.0).');
    }

    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        $sm->ensureDocumentIndexes($document, $maxTimeMs, $writeConcern, $background);
    }

    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        $sm->ensureIndexes($maxTimeMs, $writeConcern, $background);
    }

    /** @return void */
    protected function processDocumentProxy(SchemaManager $sm, string $document)
    {
        $classMetadata = $this->getMetadataFactory()->getMetadataFor($document);

        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses([$classMetadata]);
    }

    /** @return void */
    protected function processProxy(SchemaManager $sm)
    {
        $metadatas = $this->getMetadataFactory()->getAllMetadata();
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($metadatas);
    }
}
