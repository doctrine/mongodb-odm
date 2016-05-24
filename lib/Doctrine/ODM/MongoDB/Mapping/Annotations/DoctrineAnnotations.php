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

require_once __DIR__ . '/AbstractDocument.php';
require_once __DIR__ . '/Document.php';
require_once __DIR__ . '/EmbeddedDocument.php';
require_once __DIR__ . '/MappedSuperclass.php';
require_once __DIR__ . '/Inheritance.php';
require_once __DIR__ . '/InheritanceType.php';
require_once __DIR__ . '/DiscriminatorField.php';
require_once __DIR__ . '/DiscriminatorMap.php';
require_once __DIR__ . '/DiscriminatorValue.php';
require_once __DIR__ . '/DefaultDiscriminatorValue.php';
require_once __DIR__ . '/Indexes.php';
require_once __DIR__ . '/AbstractIndex.php';
require_once __DIR__ . '/Index.php';
require_once __DIR__ . '/UniqueIndex.php';
require_once __DIR__ . '/Version.php';
require_once __DIR__ . '/Lock.php';
require_once __DIR__ . '/AbstractField.php';
require_once __DIR__ . '/Field.php';
require_once __DIR__ . '/Id.php';
require_once __DIR__ . '/Hash.php';

// Don't import annotations whose names are reserved words in PHP7+
if (PHP_VERSION_ID < 70000) {
    require_once __DIR__ . '/Bool.php';
    require_once __DIR__ . '/Int.php';
    require_once __DIR__ . '/Float.php';
    require_once __DIR__ . '/String.php';
}

require_once __DIR__ . '/Boolean.php';
require_once __DIR__ . '/Integer.php';
require_once __DIR__ . '/Date.php';
require_once __DIR__ . '/Key.php';
require_once __DIR__ . '/Timestamp.php';
require_once __DIR__ . '/Bin.php';
require_once __DIR__ . '/BinFunc.php';
require_once __DIR__ . '/BinUUID.php';
require_once __DIR__ . '/BinUUIDRFC4122.php';
require_once __DIR__ . '/BinMD5.php';
require_once __DIR__ . '/BinCustom.php';
require_once __DIR__ . '/File.php';
require_once __DIR__ . '/Increment.php';
require_once __DIR__ . '/ObjectId.php';
require_once __DIR__ . '/Collection.php';
require_once __DIR__ . '/Raw.php';
require_once __DIR__ . '/EmbedOne.php';
require_once __DIR__ . '/EmbedMany.php';
require_once __DIR__ . '/ReferenceOne.php';
require_once __DIR__ . '/ReferenceMany.php';
require_once __DIR__ . '/NotSaved.php';
require_once __DIR__ . '/Distance.php';
require_once __DIR__ . '/AlsoLoad.php';
require_once __DIR__ . '/ChangeTrackingPolicy.php';
require_once __DIR__ . '/PrePersist.php';
require_once __DIR__ . '/PostPersist.php';
require_once __DIR__ . '/PreUpdate.php';
require_once __DIR__ . '/PostUpdate.php';
require_once __DIR__ . '/PreRemove.php';
require_once __DIR__ . '/PostRemove.php';
require_once __DIR__ . '/PreLoad.php';
require_once __DIR__ . '/PostLoad.php';
require_once __DIR__ . '/PreFlush.php';
require_once __DIR__ . '/HasLifecycleCallbacks.php';
require_once __DIR__ . '/ShardKey.php';
