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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;

/**
 * Command to (re)generate the hydrator classes used by doctrine.
 *
 * @since   1.0
 */
class GenerateHydratorsCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:generate:hydrators')
        ->setDescription('Generates hydrator classes for document classes.')
        ->setDefinition(array(
            new InputOption(
                'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
            new InputArgument(
                'dest-path', InputArgument::OPTIONAL,
                'The path to generate your hydrator classes. If none is provided, it will attempt to grab from configuration.'
            ),
        ))
        ->setHelp(<<<EOT
Generates hydrator classes for document classes.
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
            $destPath = $dm->getConfiguration()->getHydratorDir();
        }

        if ( ! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Hydrators destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } elseif ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Hydrators destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
            }

            // Generating Hydrators
            $dm->getHydratorFactory()->generateHydratorClasses($metadatas, $destPath);

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Hydrator classes generated to "<info>%s</INFO>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}
