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

namespace Doctrine\ODM\MongoDB;

use Doctrine\MongoDB\EagerCursor as BaseEagerCursor;

/**
 * EagerCursor wraps a Cursor instance and fetches all of its results upon
 * initialization.
 *
 * @since  1.0
 * @deprecated Deprecated in favor of using Cursor; will be removed in 2.0
 */
class EagerCursor extends Cursor
{
    /**
     * Workaround to use base cursor of \Doctrine\MongoDB\EagerCursor count method
     * since \Doctrine\MongoDB\EagerCursor::count() method does not support $foundOnly = false flag
     * {@inheritdoc}
     */
    public function count($foundOnly = false)
    {
        if (false === $foundOnly && $this->getBaseCursor() instanceof BaseEagerCursor) {
            return $this->getBaseCursor()->getCursor()->count($foundOnly);
        } else {
            return parent::count($foundOnly);
        }
    }

}
