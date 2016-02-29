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

class UpdateCommand extends AbstractCommand
{
    private $timeout;

    protected function configure()
    {
        $this
            ->setName('odm:schema:update')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout (ms) for acknowledged index creation')
            ->setDescription('Update indexes for your documents')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');

        $timeout = $input->getOption('timeout');
        $this->timeout = isset($timeout) ? (int) $timeout : null;

        $sm = $this->getSchemaManager();
        $isErrored = false;

        try {
            if (isset($class)) {
                $this->processDocumentIndex($sm, $class);
                $output->writeln(sprintf('Updated <comment>index(es)</comment> for <info>%s</info>', $class));
            } else {
                $this->processIndex($sm);
                $output->writeln('Updated <comment>indexes</comment> for <info>all classes</info>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $isErrored = true;
        }

        return $isErrored ? 255 : 0;
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     */
    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->updateDocumentIndexes($document, $this->timeout);
    }

    /**
     * @param SchemaManager $sm
     */
    protected function processIndex(SchemaManager $sm)
    {
        $sm->updateIndexes($this->timeout);
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     * @throws \BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document collection');
    }

    /**
     * @param SchemaManager $sm
     * @throws \BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a collection');
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     * @throws \BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document database');
    }

    /**
     * @param SchemaManager $sm
     * @throws \BadMethodCallException
     */
    protected function processDb(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a database');
    }
}
