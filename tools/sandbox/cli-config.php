<?php

use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;

require_once 'config.php';

$helpers = array('dm' => new DocumentManagerHelper($dm));
