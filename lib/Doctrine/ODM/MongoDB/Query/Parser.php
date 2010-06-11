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

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\DocumentManager;

/**
 * A simple parser for MongoDB Document Query Language
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Parser
{
    /**
     * The DocumentManager instance for this query
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $_dm;

    /**
     * The lexer.
     *
     * @var Doctrine\ODM\MongoDB\Query\Lexer
     */
    private $_lexer;

    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
        $this->_lexer = new Lexer;
    }

    public function parse($query, $parameters = array())
    {
        if (strpos($query, '?') !== false) {
            if (strpos($query, ':') !== false) {
                throw new \InvalidArgumentException('Cannot mixed named and regular placeholders.');
            }
            $regex = '/([=,\(][^\\\']*)(\?)/iU';
            foreach($parameters as $key => $value) {
                $query = preg_replace($regex, "\\1'{$value}'", $query, 1);
            }
        }

        $this->_lexer->reset();
        $this->_lexer->setInput($query);
        $this->_lexer->moveNext();

        $query = new Query($this->_dm);

        if ($this->_lexer->isNextToken(Lexer::T_FIND)) {
            $this->FindQuery($query, $parameters);
        } else if ($this->_lexer->isNextToken(Lexer::T_UPDATE)) {
            $this->UpdateQuery($query, $parameters);
        } else if ($this->_lexer->isNextToken(Lexer::T_INSERT)) {
            $this->InsertQuery($query, $parameters);
        } else if ($this->_lexer->isNextToken(Lexer::T_REMOVE)) {
            $this->RemoveQuery($query, $parameters);
        }

        return $query;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     * If they match, updates the lookahead token; otherwise raises a syntax error.
     *
     * @param int|string token type or value
     * @return bool True if tokens match; false otherwise.
     */
    public function match($token)
    {
        if ( ! ($this->_lexer->lookahead['type'] === $token)) {
            $this->syntaxError($this->_lexer->getLiteral($token));
        }
        $this->_lexer->moveNext();
    }

    /**
     * Generates a new syntax error.
     *
     * @param string $expected Expected string.
     * @param array $token Optional token.
     * @throws AnnotationException
     */
    private function syntaxError($expected, $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }

        $message =  "Expected {$expected}, got ";

        if ($this->_lexer->lookahead === null) {
            $message .= 'end of string';
        } else {
            $message .= "'{$token['value']}' at position {$token['position']}";
        }

        $message .= '.';

        throw new \Doctrine\ODM\MongoDB\MongoDBException($message);
    }

    private function FindQuery(Query $query, array $parameters)
    {
        $this->match(Lexer::T_FIND);

        if ($this->_lexer->isNextToken(Lexer::T_SELECT_ALL)) {
            $this->match(Lexer::T_SELECT_ALL);
            $this->match(Lexer::T_IDENTIFIER);
        } else {
            $this->match(Lexer::T_IDENTIFIER);
            $query->addSelect($this->_lexer->token['value']);
            while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);
                $this->match(Lexer::T_IDENTIFIER);
                $query->addSelect($this->_lexer->token['value']);
            }
            $this->match(Lexer::T_IDENTIFIER);
        }

        $query->find($this->_lexer->token['value']);

        if ($this->_lexer->isNextToken(Lexer::T_WHERE)) {
            $this->Where($query, $parameters);
        }

        if ($this->_lexer->isNextToken(Lexer::T_MAP)) {
            $this->Map($query, $parameters);
        }

        if ($this->_lexer->isNextToken(Lexer::T_REDUCE)) {
            $this->Reduce($query, $parameters);
        }
        
        $tokens = array(
            Lexer::T_SORT           => 'Sort',
            Lexer::T_LIMIT          => 'Limit',
            Lexer::T_SKIP           => 'Skip'
        );

        while (true) {
            $found = false;
            foreach ($tokens as $token => $method) {
                if ($this->_lexer->isNextToken($token)) {
                    $this->match($token);
                    $found = true;
                    $this->$method($query, $parameters);
                    unset($tokens[$token]);
                }
            }
            if ($found === false) {
                break;
            }
        }
    }

    private function UpdateQuery(Query $query, array $parameters)
    {
        $this->match(Lexer::T_UPDATE);
        $this->match(Lexer::T_IDENTIFIER);
        $query->update($this->_lexer->token['value']);

        $tokens = array(
            Lexer::T_SET            => 'UpdateSet',
            Lexer::T_UNSET          => 'UpdateUnset',
            Lexer::T_INC            => 'UpdateInc',
            Lexer::T_PUSH           => 'UpdatePush',
            Lexer::T_PUSHALL        => 'UpdatePushAll',
            Lexer::T_PULL           => 'UpdatePull',
            Lexer::T_PULLALL        => 'UpdatePullAll',
            Lexer::T_ADDTOSET       => 'UpdateAddToSet',
            Lexer::T_ADDMANYTOSET   => 'UpdateAddManyToSet',
            Lexer::T_POPFIRST       => 'UpdatePopFirst',
            Lexer::T_POPLAST        => 'UpdatePopLast'
        );

        while (true) {
            $found = false;
            foreach ($tokens as $token => $method) {
                if ($this->_lexer->isNextToken($token)) {
                    $this->match($token);
                    $found = true;
                    $this->$method($query, $parameters);
                    unset($tokens[$token]);
                }
            }
            if ($found === false) {
                break;
            }
        }

        if ($this->_lexer->isNextToken(Lexer::T_WHERE)) {
            $this->Where($query, $parameters);
        }
    }

    private function InsertQuery(Query $query, array $parameters)
    {
        $this->match(Lexer::T_INSERT);
        $this->match(Lexer::T_IDENTIFIER);
        $query->insert($this->_lexer->token['value']);

        $this->match(Lexer::T_SET);
        $this->InsertSet($query, $parameters);
    }

    private function InsertSet(Query $query, array $parameters)
    {
        $this->InsertSetPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->InsertSetPart($query, $parameters);
        }     
    }

    private function InsertSetPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->set($fieldName, $value, false);
    }

    private function RemoveQuery(Query $query, array $parameters)
    {
        $this->match(Lexer::T_REMOVE);
        $this->match(Lexer::T_IDENTIFIER);
        $query->remove($this->_lexer->token['value']);

        if ($this->_lexer->isNextToken(Lexer::T_WHERE)) {
            $this->Where($query, $parameters);
        }
    }

    private function Sort(Query $query)
    {
        $this->SortPart($query);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->SortPart($query);
        }
    }

    private function SortPart(Query $query)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match(Lexer::T_IDENTIFIER);
        $order = $this->_lexer->token['value'];
        $query->addSort($fieldName, $order);
    }

    private function Limit(Query $query)
    {
        $this->match($this->_lexer->lookahead['type']);
        $query->limit($this->_lexer->token['value']);
    }

    private function Skip(Query $query)
    {
        $this->match($this->_lexer->lookahead['type']);
        $query->skip($this->_lexer->token['value']);
    }

    private function Map(Query $query, array $parameters)
    {
        $this->match(Lexer::T_MAP);
        $this->match(Lexer::T_STRING);
        $query->map($this->_lexer->token['value']);
    }

    private function Reduce(Query $query, array $parameters)
    {
        $this->match(Lexer::T_REDUCE);
        $this->match(Lexer::T_STRING);
        $query->reduce($this->_lexer->token['value']);
    }

    private function Where(Query $query, array $parameters)
    {
        $this->match(Lexer::T_WHERE);
        $this->WherePart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_AND)) {
            $this->match(Lexer::T_AND);
            $this->WherePart($query, $parameters);
        }
    }

    private function WherePart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $operator = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_lexer->token['value'];
        $operators = array(
            '='      => 'addWhere',
            '!='     => 'whereNotEqual',
            '>='     => 'whereGte',
            '<='     => 'whereLte',
            '>'      => 'whereGt',
            '<'      => 'whereLt',
            'in'     => 'whereIn',
            'notIn'  => 'whereNotIn',
            'all'    => 'whereAll',
            'size'   => 'whereSize',
            'exists' => 'whereExists',
            'type'   => 'whereType'
        );
        $method = $operators[$operator];
        $value = $this->_prepareValue($value, $parameters);
        $query->$method($fieldName, $value);
    }

    private function UpdateSet(Query $query, array $parameters)
    {
        $this->UpdateSetPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdateSetPart($query, $parameters);
        }     
    }

    private function UpdateSetPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->set($fieldName, $value);
    }

    public function UpdateUnset(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $query->unsetField($this->_lexer->token['value']);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_IDENTIFIER);
            $query->unsetField($this->_lexer->token['value']);
        }
    }

    private function UpdatePush(Query $query, array $parameters)
    {
        $this->UpdatePushPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdatePushPart($query, $parameters);
        }     
    }

    private function UpdatePushPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->push($fieldName, $value);
    }

    private function UpdatePushAll(Query $query, array $parameters)
    {
        $this->UpdatePushAllPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdatePushAllPart($query, $parameters);
        }     
    }

    private function UpdatePushAllPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->pushAll($fieldName, $value);
    }

    private function UpdatePull(Query $query, array $parameters)
    {
        $this->UpdatePullPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdatePullPart($query, $parameters);
        }     
    }

    private function UpdatePullPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->pull($fieldName, $value);
    }

    private function UpdatePullAll(Query $query, array $parameters)
    {
        $this->UpdatePullAllPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdatePullAllPart($query, $parameters);
        }     
    }

    private function UpdatePullAllPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->pullAll($fieldName, $value);
    }

    private function UpdatePopFirst(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $query->popFirst($this->_lexer->token['value']);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_IDENTIFIER);
            $query->popFirst($this->_lexer->token['value']);
        }   
    }

    private function UpdatePopLast(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $query->popLast($this->_lexer->token['value']);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_IDENTIFIER);
            $query->popLast($this->_lexer->token['value']);
        }   
    }

    private function UpdateAddToSet(Query $query, array $parameters)
    {
        $this->UpdateAddToSetPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdateAddToSetPart($query, $parameters);
        }     
    }

    private function UpdateAddToSetPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->addToSet($fieldName, $value);
    }

    private function UpdateAddManyToSet(Query $query, array $parameters)
    {
        $this->UpdateAddManyToSetPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdateAddManyToSetPart($query, $parameters);
        }     
    }

    private function UpdateAddManyToSetPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->addManyToSet($fieldName, $value);
    }

    private function UpdateInc(Query $query, array $parameters)
    {
        $this->UpdateIncPart($query, $parameters);
        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->UpdateIncPart($query, $parameters);
        }
    }

    private function UpdateIncPart(Query $query, array $parameters)
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match($this->_lexer->lookahead['type']);
        $this->match($this->_lexer->lookahead['type']);
        $value = $this->_prepareValue($this->_lexer->token['value'], $parameters);
        $query->inc($fieldName, $value);
    }

    private function _prepareValue($value, array $parameters)
    {
        if (isset($parameters[$value])) {
            $value = $parameters[$value];
        }
        if ($value === 'true') {
            $value = true;
        }
        if ($value === 'false') {
            $value = false;
        }
        if (is_string($value) && strstr($value, 'json:') !== false) {
            $value = json_decode(substr($value, 5));
        }
        return $value;
    }
}