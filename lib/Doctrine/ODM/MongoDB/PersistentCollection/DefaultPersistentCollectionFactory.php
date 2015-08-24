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

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\UnitOfWork;

final class DefaultPersistentCollectionFactory implements PersistentCollectionFactory
{
    /** @var DocumentManager */
    private $dm;

    /** @var UnitOfWork */
    private $uow;

    public function __construct(DocumentManager $dm, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->uow = $uow;
    }

    public function create(array $mapping, BaseCollection $coll = null)
    {
        if ($coll === null) {
            $coll = ! empty($mapping['collectionClass'])
                    ? new $mapping['collectionClass']
                    : new ArrayCollection();
        }
        if (empty($mapping['collectionClass'])) {
            return new PersistentCollection($coll, $this->dm, $this->uow);
        }
        $className = 'PersistentCollections\\' . $mapping['collectionClass'];
        if ( ! class_exists($className)) {
            $this->generateCollectionClass($mapping['collectionClass'], $className);
        }
        $className = '\\' . $className;
        return new $className($coll, $this->dm, $this->uow);
    }

    private function generateCollectionClass($for, $targetFqcn)
    {
        $exploded = explode('\\', $targetFqcn);
        $class = array_pop($exploded);
        $namespace = join('\\', $exploded);
        $code = <<<CODE
namespace $namespace;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

class $class extends \\$for implements \\Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionInterface
{
    use \\Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionTrait;

    /**
     * @param BaseCollection \$coll
     * @param DocumentManager \$dm
     * @param UnitOfWork \$uow
     */
    public function __construct(BaseCollection \$coll, DocumentManager \$dm, UnitOfWork \$uow)
    {
        \$this->coll = \$coll;
        \$this->dm = \$dm;
        \$this->uow = \$uow;
    }

CODE;
        $rc = new \ReflectionClass($for);
        $rt = new \ReflectionClass('Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionTrait');
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // @todo harden this
            if (
                $rt->hasMethod($method->getName()) ||
                $method->isConstructor() ||
                $method->getName() === 'matching'
            ) {
                continue;
            }
            // @todo obviously this works only for functions with no args
            $code .= <<<CODE
    public function {$method->getName()}()
    {
        \$this->initialize();
        return \$this->coll->{$method->getName()}();
    }

CODE;
        }
        $code .= "\n}";
        eval($code);
    }
}
