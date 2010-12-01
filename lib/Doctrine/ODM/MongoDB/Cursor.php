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

    /** @override */
    public function __construct(MongoCursor $mongoCursor, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->mongoCursor = $mongoCursor;
        $this->unitOfWork = $uow;
        $this->class = $class;
    }

    /** @override */
    public function current()
    {
        $current = $this->mongoCursor->current();
        if ($current && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $current);
        }
        return $current ? $current : null;
    }

    public function hydrate($bool)
    {
        $this->hydrate = $bool;
        return $this->hydrate;
    }
}