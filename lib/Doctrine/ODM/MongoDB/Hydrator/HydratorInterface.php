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

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * The HydratorInterface defines methods all hydrator need to implement
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
interface HydratorInterface
{
    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param ClassMetadata $metadata The ClassMetadata instance for the passed $document.
     * @param object $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @param array $hints Any hints to account for during reconstitution/lookup of the document.
     * @return array $values The array of hydrated values.
     */
    function hydrate(ClassMetadata $metadata, $document, $data, array $hints = array());
}
