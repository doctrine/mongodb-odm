<?php

require_once 'config.php';

$helpers = array(
    'dm' => new Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($dm),
);