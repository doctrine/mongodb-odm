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
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

class Out extends Stage
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $collection;

    /**
     * @param Builder $builder
     * @param string $collection
     * @param DocumentManager $documentManager
     */
    public function __construct(Builder $builder, $collection, DocumentManager $documentManager)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
        $this->out($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$out' => $this->collection
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function out($collection)
    {
        try {
            $class = $this->dm->getClassMetadata($collection);
        } catch (BaseMappingException $e) {
            $this->collection = (string) $collection;
            return $this;
        }

        $this->fromDocument($class);
        return $this;
    }

    private function fromDocument(ClassMetadata $classMetadata)
    {
        if ($classMetadata->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInOutStage($classMetadata->name);
        }

        $this->collection = $classMetadata->getCollection();
    }
}
