<?php

require_once 'config.php';

$helpers = array(
    new Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($dm),
);