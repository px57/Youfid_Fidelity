<?php

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	exit;
}

require_once('../../lib/Logger.class.php');
require_once('dbLogInfo.php');
require_once('utils.php');
if (!isset($logger))
	$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

function doLog($message)
{
	global $logger;

	if (isset($logger))
		$logger->log('debug', 'initMarchand', $message, Logger::GRAN_MONTH);
}

doLog("Init merchant ...");

$youfidDb = new PDO($youfid_pdo_connection_string, $username, $password);

$tbl_bo_usr = "backoffice_usr";
$tbl_marchand="marchand";

$json = file_get_contents('php://input');
$jsonArray = json_decode($json);

doLog("Request::" . $json);

$jsonResult = array();

$errorMsg = "";
$error = init_marchand($jsonArray);

if ($error == FALSE) {
	$jsonResult['status'] = "error";
	$jsonResult['message'] = $errorMsg;
} else {
	$jsonResult['status'] = "ok";
	$jsonResult['message'] = $errorMsg;
}

doLog("RESPONSE=" . json_encode(array_map_utf8_encode($jsonResult)));

echo(json_encode(array_map_utf8_encode($jsonResult)));

function init_marchand($jsonArray) {
	global $youfidDb, $errorMsg, $tbl_bo_usr, $tbl_marchand, $jsonResult;

	if (!isset($jsonArray->login) || !isset($jsonArray->password))
	{
		$errorMsg = "Error: Some parameters who are mandatory are missing...";
		return FALSE;
	}

	$login = $jsonArray->login;
	$password = $jsonArray->password;

	/*
	$query = "SELECT * FROM $tbl_bo_usr WHERE `login`='"
		. mysql_real_escape_string($login)
		. "' AND `password`='"
		. mysql_real_escape_string($password)
		. "'";
	*/

	$query = $youfidDb->prepare('SELECT * FROM backoffice_usr WHERE login = :login AND password =:password');
	$query->execute(array(
		'login' => $login,
		'password' => $password
	));

	$row = fetchOne($query);
	if ($row === false)
	{
		$errorMsg = "Error: Login ou mot de passe incorrects";
		return FALSE;
	} else {
		$jsonResult['merchant_id'] = $row->id_marchand;

		// 3 6 10 (pizzahut)
		/*
		if($merchant_id == 68 || $merchant_id == 69 || $merchant_id == 256 || $merchant_id == 257
		|| $merchant_id == 258 || $merchant_id == 259 || $merchant_id == 267 || $merchant_id == 285
		|| $merchant_id == 616 || $merchant_id == 617 || $merchant_id == 618 || $merchant_id == 286
		|| $merchant_id == 192)
		*/
		$pizzahutMerchants = array(68, 69, 256, 257, 258, 259, 267, 285, 286, 584, 616, 617, 618, 619, 192);
		if(in_array($row->id_marchand, $pizzahutMerchants))
			$jsonResult['loyalty_mode'] = "1";

		// 3 6 9 (dominos)
		else if($row->id_marchand == 225 )
			$jsonResult['loyalty_mode'] = "2";

		// Rest of the merchants
		else $jsonResult['loyalty_mode'] = "0";

		// Generate session key if secure marchand
		$query = $youfidDb->prepare('SELECT id, sec_type, session_key FROM marchand WHERE id = :id');
		$query->execute(array(
			'id' => $row->id_marchand
		));

		$merchant = fetchOne($query);
		if ($merchant) {
			$jsonResult['sec_type'] = $merchant->sec_type;
			
			if ($merchant->sec_type === 'oauth1HmacSha256') {
				$sessionKey = generateSessionKey();
				$query = $youfidDb->prepare('UPDATE marchand SET session_key = :sessionKey WHERE id = :id');
				
				if(!$query->execute(array(
					'sessionKey' => $sessionKey,
					'id' => $merchant->id
				))) {
					$errorMsg = "Error: could not update key on DB: " . print_r($query->errorInfo(), true);
					return FALSE;
				}

				$jsonResult['session_key'] = $sessionKey;
			}
		}

		return TRUE;
	}
}
