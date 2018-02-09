<?php

declare(strict_types=1);

use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

require_once __DIR__ . '/config.php';

$helperSet = new HelperSet([
    'dm' => new DocumentManagerHelper($dm),
]);
