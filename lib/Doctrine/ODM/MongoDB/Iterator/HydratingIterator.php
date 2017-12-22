<?php declare(strict_types = 1);
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

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Iterator that wraps a traversable and hydrates results into objects
 *
 * @internal
 */
final class HydratingIterator implements \Iterator
{
    private $iterator;
    private $unitOfWork;
    private $class;
    private $unitOfWorkHints;

    public function __construct(\Traversable $traversable, UnitOfWork $unitOfWork, ClassMetadata $class, array $unitOfWorkHints = [])
    {
        $this->iterator = $this->wrapTraversable($traversable);
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
        $this->unitOfWorkHints = $unitOfWorkHints;
    }

    /**
     * @see http://php.net/iterator.current
     * @return mixed
     */
    public function current()
    {
        return $this->hydrate($this->iterator->current());
    }

    /**
     * @see http://php.net/iterator.mixed
     * @return mixed
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * @see http://php.net/iterator.next
     * @return void
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * @see http://php.net/iterator.rewind
     * @return void
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * @see http://php.net/iterator.valid
     * @return boolean
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    private function hydrate($document)
    {
        return $document !== null ? $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints) : null;
    }

    private function wrapTraversable(\Traversable $traversable): \Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
