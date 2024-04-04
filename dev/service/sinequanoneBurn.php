<?php

# API endpoint used by merchant-app
# POST: qr_code

require_once 'utils.php';
require_once 'dbLogInfo.php';

mysql_connect($host, $username, $password) or die('{"status":"error", "message":"cannot connect to DB"}');
mysql_select_db($db_name) or die('{"status":"error", "message":"cannot select DB"}');

$input = json_decode(file_get_contents('php://input'));


# on selectionne le user ...
$q = mysql_query("
	SELECT * FROM `mobileuser` WHERE `qr_code` LIKE '" . mysql_real_escape_string($input->qr_code) . "'
");
$mobileuser = mysql_fetch_array($q);
if(!isset($mobileuser['id']))
{
	header('Content-Type: application/json');
	echo json_encode([
		'status' => 'error',
		'message' => 'User not found',
	]);
	exit;
}


# ... et on le burn (2001 -> 2000)
$fid_status = floor($mobileuser['fid_status'] / 10) * 10;
if($fid_status == 0)
	$fid_status = '0000';

mysql_query("
	UPDATE `mobileuser` SET `fid_status` = '" . $fid_status . "' WHERE `id` = " . $mobileuser['id'] . "
");


header('Content-Type: application/json');
echo json_encode([
	'status' => 'success',
	'message' => 'User has been burned',
	'user_fid_status' => strval($fid_status),
]);