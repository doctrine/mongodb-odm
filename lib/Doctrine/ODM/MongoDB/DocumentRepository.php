<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository as BaseDocumentRepository;
use const E_USER_DEPRECATED;
use function class_exists;
use function sprintf;
use function trigger_error;

if (! class_exists(BaseDocumentRepository::class, false)) {
    @trigger_error(sprintf('The "%s" class is deprecated and will be removed in doctrine/mongodb-odm 2.0. Use "%s" instead.', DocumentRepository::class, BaseDocumentRepository::class), E_USER_DEPRECATED);
}

class_alias(BaseDocumentRepository::class, DocumentRepository::class);

if (false) {
    /**
     * This stub has two purposes:
     * - it provides a class for IDEs so they still provide autocompletion for
     *   this class even when they don't support class_alias
     * - it gets composer to think there's a class in here when using the
     *   --classmap-authoritative autoloader optimization.
     *
     * @deprecated in favor of \Doctrine\ODM\MongoDB\Repository\DocumentRepository
     */
    class DocumentRepository extends BaseDocumentRepository
    {
    }
}
