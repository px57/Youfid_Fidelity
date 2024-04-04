<?php

# prod

$db = [
	'servername' => 'db.youfid.fr',
	'username' => 'youfid',
    'password' => 'youfid',
    'dbname' => 'youfid',
];

$app->db = new mysqli($db['servername'], $db['username'], $db['password'], $db['dbname']);

if ($app->db->connect_error)
	die("db failed: " . $app->db->connect_error);

$app->db->query("SET NAMES 'utf8';");



# loyalty

$db2 = [
	'servername' => 'db.youfid.fr',
	'username' => 'youfid',
    'password' => 'youfid',
    'dbname' => 'loyalty',
];

$app->db2 = new mysqli($db2['servername'], $db2['username'], $db2['password'], $db2['dbname']);

if ($app->db2->connect_error)
	die("db failed: " . $app->db2->connect_error);

$app->db2->query("SET NAMES 'utf8';");
