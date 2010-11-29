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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Expression builder class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Expr
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * The query array built by this expression class.
     *
     * @var string
     */
    private $query = array();

    /**
     * The new object array containing a whole new document or a query containing
     * atomic operators to update a document.
     *
     * @var array
     */
    private $newObj = array();

    /**
     * The current field we are operating on.
     *
     * @var string
     */
    private $currentField;

    public function __construct(DocumentManager $dm, $cmd)
    {
        $this->dm = $dm;
        $this->cmd = $cmd;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getNewObj()
    {
        return $this->newObj;
    }

    public function getCurrentField()
    {
        return $this->currentField;
    }

    public function field($field)
    {
        $this->currentField = $field;
        return $this;
    }

    public function equals($value, array $options = array())
    {
        if ($this->currentField) {
            $this->query[$this->currentField] = $value;
        } else {
            $this->query = $value;
        }
        return $this;
    }

    public function where($javascript)
    {
        return $this->field($this->cmd . 'where')->equals($javascript);
    }

    public function operator($operator, $value)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$operator] = $value;
        } else {
            $this->query[$operator] = $value;
        }
        return $this;
    }

    public function in($values)
    {
        return $this->operator($this->cmd . 'in', $values);
    }

    public function notIn($values)
    {
        return $this->operator($this->cmd . 'nin', (array) $values);
    }

    public function notEqual($value)
    {
        return $this->operator($this->cmd . 'ne', $value);
    }

    public function gt($value)
    {
        return $this->operator($this->cmd . 'gt', $value);
    }

    public function gte($value)
    {
        return $this->operator($this->cmd . 'gte', $value);
    }

    public function lt($value)
    {
        return $this->operator($this->cmd . 'lt', $value);
    }

    public function lte($value)
    {
        return $this->operator($this->cmd . 'lte', $value);
    }

    public function range($start, $end)
    {
        return $this->operator($this->cmd . 'gte', $start)->operator($this->cmd . 'lt', $end);
    }

    public function size($size)
    {
        return $this->operator($this->cmd . 'size', $size);
    }

    public function exists($bool)
    {
        return $this->operator($this->cmd . 'exists', $bool);
    }

    public function type($type)
    {
        $map = array(
            'double' => 1,
            'string' => 2,
            'object' => 3,
            'array' => 4,
            'binary' => 5,
            'undefined' => 6,
            'objectid' => 7,
            'boolean' => 8,
            'date' => 9,
            'null' => 10,
            'regex' => 11,
            'jscode' => 13,
            'symbol' => 14,
            'jscodewithscope' => 15,
            'integer32' => 16,
            'timestamp' => 17,
            'integer64' => 18,
            'minkey' => 255,
            'maxkey' => 127
        );
        if (is_string($type) && isset($map[$type])) {
            $type = $map[$type];
        }
        return $this->operator($this->cmd . 'type', $type);
    }

    public function all($values)
    {
        return $this->operator($this->cmd . 'all', (array) $values);
    }

    public function mod($mod)
    {
        return $this->operator($this->cmd . 'mod', $mod);
    }

    public function withinBox($x1, $y1, $x2, $y2)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'within'][$this->cmd . 'box'] = array(array($x1, $y1), array($x2, $y2));
        } else {
            $this->query[$this->cmd . 'within'][$this->cmd . 'box'] = array(array($x1, $y1), array($x2, $y2));
        }
        return $this;
    }

    public function withinCenter($x, $y, $radius)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'within'][$this->cmd . 'center'] = array(array($x, $y), $radius);
        } else {
            $this->query[$this->cmd . 'within'][$this->cmd . 'center'] = array(array($x, $y), $radius);
        }
        return $this;
    }

    public function references($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));

        $reference = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id'  => $class->getDatabaseIdentifierValue($class->getIdentifierValue($document)),
            $this->cmd . 'db'  => $class->getDB()
        );

        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'elemMatch'] = $reference;
        } else {
            $this->query[$this->cmd . 'elemMatch'] = $reference;
        }

        return $this;
    }

    public function set($value, $atomic = true)
    {
        $this->requiresCurrentField();
        if ($atomic === true) {
            $this->newObj[$this->cmd . 'set'][$this->currentField] = $value;
        } else {
            if (strpos($this->currentField, '.') !== false) {
                $e = explode('.', $this->currentField);
                $current = &$this->newObj;
                foreach ($e as $v) {
                    $current[$v] = null;
                    $current = &$current[$v];
                }
                $current = $value;
            } else {
                $this->newObj[$this->currentField] = $value;
            }
        }
        return $this;
    }

    public function inc($value)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'inc'][$this->currentField] = $value;
        return $this;
    }

    public function unsetField()
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'unset'][$this->currentField] = 1;
        return $this;
    }

    public function push($value)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'push'][$this->currentField] = $value;
        return $this;
    }

    public function pushAll(array $valueArray)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'pushAll'][$this->currentField] = $valueArray;
        return $this;
    }

    public function addToSet($value)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'addToSet'][$this->currentField] = $value;
        return $this;
    }

    public function addManyToSet(array $values)
    {
        $this->requiresCurrentField();
        if ( ! isset($this->newObj[$this->cmd . 'addToSet'][$this->currentField])) {
            $this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'] = array();
        }
        if ( ! is_array($this->newObj[$this->cmd . 'addToSet'][$this->currentField])) {
            $this->newObj[$this->cmd . 'addToSet'][$this->currentField] = array($this->cmd . 'each' => array($this->newObj[$this->cmd . 'addToSet'][$this->currentField]));
        }
        $this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'] = array_merge_recursive($this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'], $values);
    }

    public function popFirst()
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'pop'][$this->currentField] = 1;
        return $this;
    }

    public function popLast()
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'pop'][$this->currentField] = -1;
        return $this;
    }

    public function pull($value)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'pull'][$this->currentField] = $value;
        return $this;
    }

    public function pullAll(array $valueArray)
    {
        $this->requiresCurrentField();
        $this->newObj[$this->cmd . 'pullAll'][$this->currentField] = $valueArray;
        return $this;
    }

    public function addOr($expression)
    {
        if ($expression instanceof Expr) {
            $expression = $expression->getQuery();
        }
        $this->query[$this->cmd . 'or'][] = $expression;
        return $this;
    }

    public function elemMatch($expression)
    {
        if ($expression instanceof Expr) {
            $expression = $expression->getQuery();
        }
        return $this->operator($this->cmd . 'elemMatch', $expression);
    }

    public function not($expression)
    {
        if ($expression instanceof Expr) {
            $expression = $expression->getQuery();
        }
        return $this->operator($this->cmd . 'not', $expression);
    }

    private function requiresCurrentField()
    {
        if ( ! $this->currentField) {
            throw new \LogicException('This method requires you set a current field using field().');
        }
    }
}