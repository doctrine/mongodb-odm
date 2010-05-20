<?php

$pdo = new PDO('mysql:host=localhost;dbname=performance', 'root', null);
$stmt = $pdo->prepare('INSERT INTO user (username, password) VALUES (?, ?)');
$stmt->execute(array('user', 'password'));