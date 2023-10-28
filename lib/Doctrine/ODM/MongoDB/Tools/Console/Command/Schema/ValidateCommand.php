<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tools\Console\Command\CommandCompatibility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function serialize;
use function sprintf;
use function unserialize;

class ValidateCommand extends Command
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        $this
            ->setName('odm:schema:validate')
            ->setDescription('Validates if document mapping stays the same after serializing into cache.')
            ->setDefinition([])
            ->setHelp(<<<'EOT'
Validates if document mapping stays the same after serializing into cache.
EOT
            );
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();
        assert($dm instanceof DocumentManager);
        $metadataFactory = $dm->getMetadataFactory();

        $errors = 0;
        foreach ($metadataFactory->getAllMetadata() as $meta) {
            // Don't use === to compare as that will always evaluate to false since we receive a different object
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            if ($meta == unserialize(serialize($meta))) {
                continue;
            }

            ++$errors;
            $output->writeln(sprintf('%s has mapping issues.', $meta->getName()));
        }

        if ($errors) {
            $output->writeln(sprintf('<error>%d document(s) have mapping issues.</error>', $errors));
        } else {
            $output->writeln('All documents are OK!');
        }

        return $errors ? 255 : 0;
    }
}
