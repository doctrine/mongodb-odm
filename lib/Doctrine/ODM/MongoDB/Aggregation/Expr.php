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

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\MongoDB\Aggregation\Expr as BaseExpr;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Expr extends BaseExpr
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @inheritDoc
     */
    public function __construct(DocumentManager $dm, ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->class = $class;
    }

    /**
     * @param string $fieldName
     * @return self
     */
    public function field($fieldName)
    {
        $fieldName = $this->getDocumentPersister()->prepareFieldName($fieldName);

        parent::field($fieldName);

        return $this;
    }

    /**
     * @param mixed|self $expression
     * @return mixed
     */
    protected function ensureArray($expression)
    {
        // Convert field names in expressions
        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        // Convert PHP types to MongoDB types for everything else
        return Type::convertPHPToDatabaseValue(parent::ensureArray($expression));
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
