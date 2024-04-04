<?php

////////////////////////////////////////
// DataBase Properties

define('YF_DB_HOST', 'db.youfid.fr');
define('YF_DB_NAME', 'youfid');
define('YF_DB_USER', 'youfid');
define('YF_DB_PASS', 'youfid');

$host = YF_DB_HOST;
$username = YF_DB_USER;
$password = YF_DB_PASS;
$db_name = YF_DB_NAME;

$youfid_pdo_connection_string = "mysql:host=$host;dbname=$db_name";