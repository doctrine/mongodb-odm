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

/**
 * Abstract factory for creating persistent collection classes.
 *
 * @since 1.1
 */
abstract class AbstractPersistentCollectionFactory implements PersistentCollectionFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DocumentManager $dm, array $mapping, BaseCollection $coll = null)
    {
        if ($coll === null) {
            $coll = ! empty($mapping['collectionClass'])
                ? $this->createCollectionClass($mapping['collectionClass'])
                : new ArrayCollection();
        }

        if (empty($mapping['collectionClass'])) {
            return new PersistentCollection($coll, $dm, $dm->getUnitOfWork());
        }

        $className = $dm->getConfiguration()->getPersistentCollectionGenerator()
            ->loadClass($mapping['collectionClass'], $dm->getConfiguration()->getAutoGeneratePersistentCollectionClasses());

        return new $className($coll, $dm, $dm->getUnitOfWork());
    }

    /**
     * Creates instance of collection class to be wrapped by PersistentCollection.
     *
     * @param string $collectionClass FQCN of class to instantiate
     * @return BaseCollection
     */
    abstract protected function createCollectionClass($collectionClass);
}
