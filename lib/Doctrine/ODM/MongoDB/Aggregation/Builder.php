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

use Doctrine\MongoDB\Aggregation\Builder as BaseBuilder;
use Doctrine\MongoDB\CommandCursor as BaseCommandCursor;
use Doctrine\ODM\MongoDB\CommandCursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;

/**
 * Fluent interface for building aggregation pipelines.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class Builder extends BaseBuilder
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance.
     *
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * @var string
     */
    private $hydrationClass;

    /**
     * Create a new aggregation builder.
     *
     * @param DocumentManager $dm
     * @param string $documentName
     */
    public function __construct(DocumentManager $dm, $documentName)
    {
        $this->dm = $dm;
        $this->class = $this->dm->getClassMetadata($documentName);

        parent::__construct($this->dm->getDocumentCollection($documentName));
    }

    /**
     * {@inheritdoc}
     */
    public function execute($options = array())
    {
        // Force cursor to be used
        $options = array_merge($options, array('cursor' => true));

        $cursor = parent::execute($options);

        return $this->prepareCursor($cursor);
    }

    /**
     * @param string $className
     *
     * @return self
     */
    public function hydrate($className)
    {
        $this->hydrationClass = $className;

        return $this;
    }

    /**
     * @return QueryExpr
     */
    public function matchExpr()
    {
        $expr = new QueryExpr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * @return Expr
     */
    public function expr()
    {
        return new Expr($this->dm, $this->class);
    }

    /**
     * @param BaseCommandCursor $cursor
     *
     * @return CommandCursor
     */
    protected function prepareCursor(BaseCommandCursor $cursor)
    {
        $class = null;
        if ($this->hydrationClass) {
            $class = $this->dm->getClassMetadata($this->hydrationClass);
        }

        return new CommandCursor($cursor, $this->dm->getUnitOfWork(), $class);
    }
}
