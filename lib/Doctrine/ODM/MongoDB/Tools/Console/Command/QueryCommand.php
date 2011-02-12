<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

/**
 * Command to query mongodb and inspect the outputted results from your document classes.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class QueryCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:query')
        ->setDescription('Query mongodb and inspect the outputted results from your document classes.')
        ->setDefinition(array(
            new InputArgument(
                'class', InputArgument::REQUIRED,
                'The class to query.'
            ),
            new InputArgument(
                'query', InputArgument::REQUIRED,
                'The query to execute and output the results for.'
            ),
            new InputOption(
                'hydrate', null, InputOption::VALUE_NONE,
                'Whether or not to hydrate the results in to document objects.'
            ),
            new InputOption(
                'skip', null, InputOption::VALUE_REQUIRED,
                'The number of documents to skip in the cursor.'
            ),
            new InputOption(
                'limit', null, InputOption::VALUE_REQUIRED,
                'The number of documents to return.'
            ),
            new InputOption(
                'depth', null, InputOption::VALUE_REQUIRED,
                'Dumping depth of Document graph.', 7
            )
        ))
        ->setHelp(<<<EOT
Execute a query and output the results.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('dm')->getDocumentManager();
        $query = json_decode($input->getArgument('query'));
        $cursor = $dm->getRepository($input->getArgument('class'))->findAll((array) $query);
        $cursor->hydrate((bool) $input->getOption('hydrate'));

        $depth = $input->getOption('depth');

        if ( ! is_numeric($depth)) {
            throw new \LogicException("Option 'depth' must contain an integer value");
        }

        if (($skip = $input->getOption('skip')) !== null) {
            if ( ! is_numeric($skip)) {
                throw new \LogicException("Option 'skip' must contain an integer value");
            }

            $cursor->skip((int) $skip);
        }

        if (($limit = $input->getOption('limit')) !== null) {
            if ( ! is_numeric($limit)) {
                throw new \LogicException("Option 'limit' must contain an integer value");
            }

            $cursor->limit((int) $limit);
        }

        $resultSet = $cursor->toArray();

        \Doctrine\Common\Util\Debug::dump($resultSet, $depth);
    }
}