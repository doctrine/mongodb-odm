<?php

use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

require_once __DIR__ . '/config.php';

$helperSet = new HelperSet(array(
    'dm' => new DocumentManagerHelper($dm),
));
