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

use Doctrine\ODM\MongoDB\MongoArrayIterator;

/**
 * GeoLocationFindQuery
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class GeoLocationFindQuery extends AbstractQuery
{
    protected $query;
    protected $near;
    protected $limit;
    protected $hydrate;

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function setNear($near)
    {
        $this->near = $near;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function setHydrate($hydrate)
    {
        $this->hydrate = $hydrate;
    }

    public function execute(array $options = array())
    {
        $command = array(
            'geoNear' => $this->dm->getDocumentCollection($this->class->name)->getName(),
            'near' => $this->near,
            'query' => $this->query
        );
        if ($this->limit) {
            $command['num'] = $this->limit;
        }
        $result = $this->dm->getDocumentDB($this->class->name)
            ->command($command);
        if ( ! isset($result['results'])) {
            return new MongoArrayIterator(array());
        }
        if ($this->hydrate) {
            $uow = $this->dm->getUnitOfWork();
            $documents = array();
            foreach ($result['results'] as $result) {
                $document = $result['obj'];
                if ($this->class->distance) {
                    $document[$this->class->distance] = $result['dis'];
                }
                $documents[] = $uow->getOrCreateDocument($this->class->name, $document);
            }
            $results = $documents;
        } else {
            $results = $result['results'];
        }
        return new MongoArrayIterator($results);
    }
}