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

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;
use Doctrine\MongoDB\Aggregation\Stage as BaseStage;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

class Out extends BaseStage\Out
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @param Builder $builder
     * @param string $collection
     * @param DocumentManager $documentManager
     */
    public function __construct(Builder $builder, $collection, DocumentManager $documentManager)
    {
        $this->dm = $documentManager;

        parent::__construct($builder, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function out($collection)
    {
        try {
            $class = $this->dm->getClassMetadata($collection);
        } catch (BaseMappingException $e) {
            return parent::out($collection);
        }

        return $this->fromDocument($class);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return $this
     *
     * @throws MappingException
     */
    private function fromDocument(ClassMetadata $classMetadata)
    {
        if ($classMetadata->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInOutStage($classMetadata->name);
        }

        return parent::out($classMetadata->getCollection());
    }
}
