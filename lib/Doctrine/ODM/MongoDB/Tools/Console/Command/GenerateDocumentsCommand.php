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
use Doctrine\ODM\MongoDB\Tools\DocumentGenerator;
use Doctrine\ODM\MongoDB\Tools\DisconnectedClassMetadataFactory;

/**
 * Command to generate document classes and method stubs from your mapping information.
 *
 * @since   1.0
 */
class GenerateDocumentsCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:generate:documents')
        ->setDescription('Generate document classes and method stubs from your mapping information.')
        ->setDefinition(array(
            new InputOption(
                'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
            new InputArgument(
                'dest-path', InputArgument::REQUIRED, 'The path to generate your document classes.'
            ),
            new InputOption(
                'generate-annotations', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if the generator should generate annotation metadata on documents.', false
            ),
            new InputOption(
                'generate-methods', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if the generator should generate stub methods on documents.', true
            ),
            new InputOption(
                'regenerate-documents', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if the generator should regenerate a document if it exists.', false
            ),
            new InputOption(
                'update-documents', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if the generator should only update a document if it exists.', true
            ),
            new InputOption(
                'extend', null, InputOption::VALUE_OPTIONAL,
                'Defines a base class to be extended by generated document classes.'
            ),
            new InputOption(
                'num-spaces', null, InputOption::VALUE_OPTIONAL,
                'Defines the number of indentation spaces.', 4
            ),
            new InputOption(
                'no-backup', null, InputOption::VALUE_NONE,
                'Flag to define if the generator should provide a backup file of exisiting code.'
            )
        ))
        ->setHelp(<<<EOT
Generate document classes and method stubs from your mapping information.

If you use the <comment>--update-documents</comment> or <comment>--regenerate-documents</comment> flags your exisiting
code gets overwritten. The DocumentGenerator will only append new code to your
file and will not delete the old code. However this approach may still be prone
to error and we suggest you use code repositories such as GIT or SVN to make
backups of your code.

It makes sense to generate the document code if you are using documents as Data
Access Objects only and dont put much additional logic on them. If you are
however putting much more logic on the documents you should refrain from using
the document-generator and code your documents manually.

<error>Important:</error> Even if you specified Inheritance options in your
XML or YAML Mapping files the generator cannot generate the base and
child classes for you correctly, because it doesn't know which
class is supposed to extend which. You have to adjust the document
code manually for inheritance to work!
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();
        
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setDocumentManager($dm);
        $cmf->setConfiguration($dm->getConfiguration());
        $metadatas = $cmf->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));
        
        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Documents destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } elseif ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Documents destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            // Create DocumentGenerator
            $documentGenerator = new DocumentGenerator();

            $documentGenerator->setGenerateAnnotations($input->getOption('generate-annotations'));
            $documentGenerator->setGenerateStubMethods($input->getOption('generate-methods'));
            $documentGenerator->setRegenerateDocumentIfExists($input->getOption('regenerate-documents'));
            $documentGenerator->setUpdateDocumentIfExists($input->getOption('update-documents'));
            $documentGenerator->setBackupExisting(!$input->getOption('no-backup'));
            $documentGenerator->setNumSpaces($input->getOption('num-spaces'));

            if (($extend = $input->getOption('extend')) !== null) {
                $documentGenerator->setClassToExtend($extend);
            }

            foreach ($metadatas as $metadata) {
                $output->writeln(
                    sprintf('Processing document "<info>%s</info>".', $metadata->name)
                );
            }

            // Generating Documents
            $documentGenerator->generate($metadatas, $destPath);

            // Outputting information message
            $output->writeln(array(
                '',
                sprintf('Document classes have been generated to "<info>%s</info>".', $destPath)
            ));
        } else {
            $output->writeln('No Metadata Classes to process.');
        }
    }
}
