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

namespace Doctrine\ODM\MongoDB\Mapping;

use const E_USER_DEPRECATED;
use function class_exists;
use function sprintf;
use function trigger_error;

if (! class_exists(ClassMetadata::class, false)) {
    @trigger_error(sprintf('The "%s" class is deprecated and will be removed in doctrine/mongodb-odm 2.0. Use "%s" instead.', ClassMetadataInfo::class, ClassMetadata::class), E_USER_DEPRECATED);
}

class_alias(ClassMetadata::class, ClassMetadataInfo::class);

if (false) {
    /**
     * This stub has two purposes:
     * - it provides a class for IDEs so they still provide autocompletion for
     *   this class even when they don't support class_alias
     * - it gets composer to think there's a class in here when using the
     *   --classmap-authoritative autoloader optimization.
     *
     * @deprecated in favor of ClassMetadata
     */
    class ClassMetadataInfo extends ClassMetadata
    {
    }
}
