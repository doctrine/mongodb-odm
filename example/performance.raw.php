<?php

$mongo = new Mongo();

$start = microtime(true);

/*
$user = array(
    'username' => 'user',
    'password' => 'password'
);
$mongo->performance->users->insert($user);
*/

$user = $mongo->performance->users->findOne(array('username' => 'user'));

$end = microtime(true);
$total = $end - $start;

print_r($user);

echo "Raw MongoDB Extension: " . $total."\n";