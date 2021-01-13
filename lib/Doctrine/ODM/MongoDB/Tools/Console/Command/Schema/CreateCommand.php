<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_filter;
use function assert;
use function is_string;
use function sprintf;
use function ucfirst;

class CreateCommand extends AbstractCommand
{
    /** @var string[] */
    private $createOrder = [self::COLLECTION, self::INDEX];

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $create = array_filter($this->createOrder, static function ($option) use ($input) {
            return $input->getOption($option);
        });

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

    protected function processDocumentProxy(SchemaManager $sm, string $document)
    {
        $classMetadata = $this->getMetadataFactory()->getMetadataFor($document);
        assert($classMetadata instanceof ClassMetadata);

        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses([$classMetadata]);
    }

    protected function processProxy(SchemaManager $sm)
    {
        /** @var ClassMetadata[] $metadatas */
        $metadatas = $this->getMetadataFactory()->getAllMetadata();
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($metadatas);
    }
}
