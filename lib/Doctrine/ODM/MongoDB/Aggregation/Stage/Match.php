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

use Doctrine\MongoDB\Aggregation\Stage as BaseStage;
use const E_USER_DEPRECATED;
use function sprintf;
use function func_get_args;
use function func_num_args;
use function trigger_error;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Match extends BaseStage\Match
{
    /**
     * @param object $document
     * @return $this
     */
    public function references($document)
    {
        $this->query->references($document);

        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function includesReferenceTo($document)
    {
        $this->query->includesReferenceTo($document);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method is deprecated and will be removed in MongoDB ODM 2.0.
     */
    public function debug($name = null)
    {
        @trigger_error(sprintf('The "%s" method is deprecated and will be removed in doctrine/mongodb-odm 2.0.', __METHOD__), E_USER_DEPRECATED);

        return parent::debug($name);
    }

    /**
     * {@inheritdoc}
     */
    public function geoWithinPolygon()
    {
        if (func_num_args() < 3) {
            @trigger_error(sprintf('Calling "%s" with fewer than 3 arguments was deprecated in doctrine/mongodb-odm 1.3 and will require at least 3 arguments in 2.0.', __METHOD__), E_USER_DEPRECATED);
        }

        return parent::geoWithinPolygon(...func_get_args());
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method is deprecated and will be removed in MongoDB ODM 2.0.
     */
    public function maxDistance($maxDistance)
    {
        @trigger_error(sprintf('The "%s" method is deprecated and will be removed in doctrine/mongodb-odm 2.0.', __METHOD__), E_USER_DEPRECATED);

        return parent::maxDistance($maxDistance);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method is deprecated and will be removed in MongoDB ODM 2.0.
     */
    public function minDistance($minDistance)
    {
        @trigger_error(sprintf('The "%s" method is deprecated and will be removed in doctrine/mongodb-odm 2.0.', __METHOD__), E_USER_DEPRECATED);

        return parent::minDistance($minDistance);
    }
}
