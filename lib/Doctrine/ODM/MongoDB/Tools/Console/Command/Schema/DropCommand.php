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

class DropCommand extends AbstractCommand
{
    private $dropOrder = array(self::INDEX, self::COLLECTION, self::DB);

    protected function configure()
    {
        $this
            ->setName('odm:schema:drop')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Drop databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Drop collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Drop indexes')
            ->setDescription('Drop databases, collections and indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->dropOrder as $option) {
            if ($input->getOption($option)) {
                $drop[] = $option;
            }
        }

        // Default to the full drop order if no options were specified
        $drop = empty($drop) ? $this->dropOrder : $drop;

        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();
        $isErrored = false;

        foreach ($drop as $option) {
            try {
                if (isset($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class);
                } else {
                    $this->{'process' . ucfirst($option)}($sm);
                }
                $output->writeln(sprintf(
                    'Dropped <comment>%s%s</comment> for <info>%s</info>',
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
        $sm->dropDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->dropCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        $sm->dropDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->dropDatabases();
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->deleteDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->deleteIndexes();
    }
}
