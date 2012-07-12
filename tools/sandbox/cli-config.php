<?php

use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;

require_once 'config.php';

$helpers = array(new DocumentManagerHelper($dm));
