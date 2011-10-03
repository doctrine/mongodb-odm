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

use \MongoCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;

/**
 * Cursor extends the default Doctrine\MongoDB\Cursor implementation and changes the default
 * data returned to be mapped Doctrine document class instances. To disable the hydration
 * use hydrate(false) and the Cursor will give you normal document arrays instance of objects.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Cursor extends \Doctrine\MongoDB\Cursor
{
    /**
     * Whether or not to hydrate the data to documents.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     *
     * @var boolean
     */
    private $refresh = false;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $unitOfWork;

    /**
     * The ClassMetadata instance.
     *
     * @var Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * The array of hints for the UnitOfWork.
     *
     * @var array
     */
    private $hints = array();

    /** @override */
    public function __construct(MongoCursor $mongoCursor, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($mongoCursor);
        $this->unitOfWork = $uow;
        $this->class = $class;
    }

    /** @override */
    public function current()
    {
        $current = parent::current();
        if ($current && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $current, $this->hints);
        }
        return $current ? $current : null;
    }

    /** @override */
    public function getNext()
    {
        $next = parent::getNext();
        if ($next && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $next, $this->hints);
        }
        return $next ? $next : null;
    }

    /**
     * Set whether to hydrate the documents to objects or not.
     *
     * @param boolean $bool
     */
    public function hydrate($bool = true)
    {
        $this->hydrate = $bool;
        return $this;
    }

    /**
     * Sets whether to refresh the documents data if it already exists in the identity map.
     *
     * @param boeolan $bool
     */
    public function refresh($bool = true)
    {
        $this->refresh = $bool;
        if ($this->refresh) {
            $this->hints[Query::HINT_REFRESH] = true;
        } else {
            unset($this->hints[Query::HINT_REFRESH]);
        }
        return $this;
    }
}
