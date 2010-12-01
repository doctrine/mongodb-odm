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
 * Query expression builder for ODM.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Expr extends \Doctrine\MongoDB\Query\Expr
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    public function __construct(DocumentManager $dm, $cmd)
    {
        $this->dm = $dm;
        $this->cmd = $cmd;
    }

    public function references($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));

        $reference = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id'  => $class->getDatabaseIdentifierValue($class->getIdentifierValue($document)),
            $this->cmd . 'db'  => $class->getDatabase()
        );

        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'elemMatch'] = $reference;
        } else {
            $this->query[$this->cmd . 'elemMatch'] = $reference;
        }

        return $this;
    }
}