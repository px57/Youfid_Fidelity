<?php

# API endpoint used by merchant-app
# POST: qr_code + ticket

require_once 'utils.php';
require_once 'dbLogInfo.php';

$db_youfid  = new mysqli($host, $username, $password, "youfid");
$sinequanone_default_marchand_id = 1685;

$input = json_decode(file_get_contents('php://input'));
$ticket_id = $db_youfid->real_escape_string(@$input->ticket);
$qr_code = $db_youfid->real_escape_string(@$input->qr_code);
$amount = str_replace(',', '.', @$input->amount);
$amount = $db_youfid->real_escape_string(intval($amount));

$merchant_id = $db_youfid->real_escape_string(@$input->merchant_id);
if(!$merchant_id)
	$merchant_id = $sinequanone_default_marchand_id;

$ticket_id = date('Ymd', time()) . '00' . $ticket_id;


# on selectionne le user
$mobileuser = $db_youfid->query("
	SELECT * FROM `mobileuser` WHERE `qr_code` LIKE '" . $qr_code . "'
")->fetch_array(MYSQLI_ASSOC);

if(!isset($mobileuser['id']))
{
	header('Content-Type: application/json');
	echo json_encode([
		'status' => 'error',
		'message' => 'User not found',
	]);
	exit;
}

# on ouvre le ticket ...
$db_youfid->query("
	INSERT INTO `sinequanone_tickets` ( `ticket_id` , `created_at` ) VALUES ( '" . $ticket_id . "', NOW() )
");
# et on l'update (réconciliation : user/ticket)
$db_youfid->query("
	UPDATE `sinequanone_tickets`
	SET
		`mobileuser_id` = " . $mobileuser['id'] . " ,
		`amount` = '" . intval($amount) . "',
		`merchant_id` = '" . $merchant_id . "'
	WHERE `ticket_id` = '" . $ticket_id . "'
");


$total_amount_temp = $db_youfid->query("
	SELECT SUM( amount ) AS total_amount
	FROM `sinequanone_tickets`
	WHERE 	`mobileuser_id` = " . $mobileuser['id'] . " AND
			`created_at` > '" . date('Y-m-d H:i:s', time() - (18 * 30 * 24 * 3600)) . "'
")->fetch_array(MYSQLI_ASSOC)['total_amount'] + $amount;

$fid_status_temp = 9990000;
if($total_amount_temp > 20000) $fid_status_temp = 9991000;
if($total_amount_temp > 40000) $fid_status_temp = 9992000;
if($total_amount_temp > 60000) $fid_status_temp = 9993000;
$fid_status_temp = $fid_status_temp + ( $mobileuser['fid_status'] - (floor($mobileuser['fid_status'] / 10) * 10) );
$fid_status_temp = str_replace('999', '', strval($fid_status_temp));


header('Content-Type: application/json');
echo json_encode([
	'status' => 'success',
	'message' => 'Ticket has been saved',
	'user_fid_status' => $mobileuser['fid_status'],
	'user_fid_status_temp' => $fid_status_temp,
]);
