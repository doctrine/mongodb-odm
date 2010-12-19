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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\MongoDB\Loggable;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * LoggableCursor adds logging to the default ODM cursor.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class LoggableCursor extends Cursor implements Loggable
{
    /**
     * A callable for logging statements.
     *
     * @var mixed
     */
    protected $loggerCallable;

    /**
     * The query array that was used when creating this cursor.
     *
     * @var array
     */
    protected $query = array();

    /**
     * The array of fields that were selected when creating this cursor.
     *
     * @var array
     */
    protected $fields = array();

    public function __construct(\MongoCursor $mongoCursor, UnitOfWork $uow, ClassMetadata $class, $loggerCallable, array $query, array $fields)
    {
        parent::__construct($mongoCursor, $uow, $class);
        $this->loggerCallable = $loggerCallable;
        $this->query = $query;
        $this->fields = $fields;
    }

    /**
     * Gets the query array that was used when creating this cursor.
     *
     * @return array $query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Gets the array of fields that were selected when creating this cursor.
     *
     * @return array $fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /** @proxy */
    public function sort($fields)
    {
        $this->log(array(
            'sort' => true,
            'sortFields' => $fields
        ));

        return parent::sort($fields);
    }

    /**
     * Log something using the configured logger callable.
     *
     * @param array $log The array of data to log.
     */
    public function log(array $log)
    {
        $log['query'] = $this->query;
        $log['fields'] = $this->fields;
        call_user_func_array($this->loggerCallable, array($log));
    }
}