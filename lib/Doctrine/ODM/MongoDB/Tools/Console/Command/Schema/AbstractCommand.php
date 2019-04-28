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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractCommand extends Command
{
    const DB = 'db';
    const COLLECTION = 'collection';
    const INDEX = 'index';

    abstract protected function processDocumentCollection(SchemaManager $sm, $document);

    abstract protected function processCollection(SchemaManager $sm);

    abstract protected function processDocumentDb(SchemaManager $sm, $document);

    abstract protected function processDb(SchemaManager $sm);

    abstract protected function processDocumentIndex(SchemaManager $sm, $document);

    abstract protected function processIndex(SchemaManager $sm);

    /**
     * @return SchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }

    protected function addTimeoutOptions()
    {
        $this
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout (ms) for acknowledged commands. This option is deprecated and will be dropped in 2.0. Use the maxTimeMs option instead.')
            ->addOption('maxTimeMs', null, InputOption::VALUE_REQUIRED, 'An optional maxTimeMs that will be used for all schema operations.');

        return $this;
    }

    /**
     * Returns the appropriate timeout value
     *
     * @return int|null
     */
    protected function getTimeout(InputInterface $input)
    {
        $maxTimeMs = $input->getOption('maxTimeMs');
        $timeout = $input->getOption('timeout');

        if (isset($maxTimeMs)) {
            return (int) $maxTimeMs;
        } elseif (! isset($timeout)) {
            return null;
        }

        @trigger_error(sprintf('The "timeout" option for command "%s" is deprecated and will be removed in doctrine/mongodb-odm 2.0. Use the maxTimeMs option instead.', static::class), E_USER_DEPRECATED);

        return (int) $timeout;
    }
}
