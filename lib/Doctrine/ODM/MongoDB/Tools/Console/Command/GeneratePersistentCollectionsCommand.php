<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Command to (re)generate the persistent collection classes used by doctrine.
 *
 * @since 1.1
 */
class GeneratePersistentCollectionsCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('odm:generate:persistent-collections')
            ->setDescription('Generates persistent collection classes for custom collections.')
            ->setDefinition(array(
                new InputOption(
                    'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'A string pattern used to match documents that should be processed.'
                ),
                new InputArgument(
                    'dest-path', InputArgument::OPTIONAL,
                    'The path to generate your proxy classes. If none is provided, it will attempt to grab from configuration.'
                ),
            ))
            ->setHelp(<<<EOT
Generates persistent collection classes for custom collections.
EOT
            );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();

        $metadatas = $dm->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        if (($destPath = $input->getArgument('dest-path')) === null) {
            $destPath = $dm->getConfiguration()->getPersistentCollectionDir();
        }

        if ( ! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Persistent collections destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } elseif ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Persistent collections destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            $generated = [];
            $collectionGenerator = $dm->getConfiguration()->getPersistentCollectionGenerator();
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
                foreach ($metadata->getAssociationNames() as $fieldName) {
                    $mapping = $metadata->getFieldMapping($fieldName);
                    if (empty($mapping['collectionClass']) || isset($generated[$mapping['collectionClass']])) {
                        continue;
                    }
                    $generated[$mapping['collectionClass']] = true;
                    $output->write(
                        sprintf('Generating class for "<info>%s</info>"', $mapping['collectionClass']) . PHP_EOL
                    );
                    $collectionGenerator->generateClass($mapping['collectionClass'], $destPath);
                }
            }

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Persistent collections classes generated to "<info>%s</INFO>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}
