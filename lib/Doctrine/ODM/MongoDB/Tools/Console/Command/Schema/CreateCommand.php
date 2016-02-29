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

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand
{
    private $createOrder = array(self::DB, self::COLLECTION, self::INDEX);

    private $timeout;

    protected function configure()
    {
        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout (ms) for acknowledged index creation')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Create databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->setDescription('Create databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->createOrder as $option) {
            if ($input->getOption($option)) {
                $create[] = $option;
            }
        }

        // Default to the full creation order if no options were specified
        $create = empty($create) ? $this->createOrder : $create;

        $class = $input->getOption('class');

        $timeout = $input->getOption('timeout');
        $this->timeout = isset($timeout) ? (int) $timeout : null;

        $sm = $this->getSchemaManager();
        $isErrored = false;

        foreach ($create as $option) {
            try {
                if (isset($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class);
                } else {
                    $this->{'process' . ucfirst($option)}($sm);
                }
                $output->writeln(sprintf(
                    'Created <comment>%s%s</comment> for <info>%s</info>',
                    $option,
                    (isset($class) ? (self::INDEX === $option ? '(es)' : '') : (self::INDEX === $option ? 'es' : 's')),
                    (isset($class) ? $class : 'all classes')
                ));
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $isErrored = true;
            }
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        $sm->createDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->createCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        $sm->createDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->createDatabases();
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->ensureDocumentIndexes($document, $this->timeout);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureIndexes($this->timeout);
    }

    protected function processDocumentProxy(SchemaManager $sm, $document)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses(array($this->getMetadataFactory()->getMetadataFor($document)));
    }

    protected function processProxy(SchemaManager $sm)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($this->getMetadataFactory()->getAllMetadata());
    }
}
