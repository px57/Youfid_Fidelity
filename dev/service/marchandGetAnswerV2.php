<?php

// BE CAREFULL : LOGS CAN NOTBE INCLUDED IN PREPROD.

	//require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	//require_once('Logger.class.php');

	require_once '../../lib/db_functions.php';
	require_once 'utils.php';



$tbl_name="marchand_has_mobileuser";
require_once('dbLogInfo.php');

function utf8_converter($array)
{
	array_walk_recursive($array, function(&$item, $key){
		if(!mb_detect_encoding($item, 'utf-8', true)){
			$item = utf8_encode($item);
		}
	});

	return $array;
}

////////////////////////////////////////
// Error properties
$error = false;
$errorMsg = "";
$isReg = true;
////////////////////////////////////////
// DataBase connection
mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');
$json = file_get_contents('php://input');
$jsonArray = json_decode($json);

//$logger->log('debug', 'getAnswer', "Request=" . $json, Logger::GRAN_MONTH);

$jsonResult = [];

$jsonResult['status'] = 'error';

/*
 * Check if mobileuser exists
 */
if(isset($jsonArray->qr_code)) {
	$sqlGetUser = "
SELECT mobileuser.id
FROM mobileuser
WHERE `qr_code` = '"
		.mysql_real_escape_string($jsonArray->qr_code)
		."'";
	$resultUser = mysql_query($sqlGetUser);
	$rowUser = mysql_fetch_array($resultUser);
}

if(isset($jsonArray->merchant_id)) {
	$sqlGetMarchand = "
SELECT marchand.id, marchand.supermarchand_id
FROM marchand
WHERE `id` = '"
		.mysql_real_escape_string($jsonArray->merchant_id)
		."'";
	$resultMarchand = mysql_query($sqlGetMarchand);
	$rowMarchand = mysql_fetch_array($resultMarchand);
}

if(isset($jsonArray->answer->survey)) {

	$values = array(
		intval($jsonArray->answer->survey),
		intval($rowMarchand['id']),
		intval($rowUser['id']),
		intval($jsonArray->answer->note),
		intval($rowMarchand['supermarchand_id']) ? intval($rowMarchand['supermarchand_id']) : 'NULL'
	);

	$sqlGetSurvey = "
INSERT INTO survey_results
(question_id, marchand_id, user_id, result, supermarchand_id, added) VALUES ( ".implode(',', $values).", NOW())";

	$resultAnswer = mysql_query($sqlGetSurvey);

	if($resultAnswer) {
		$jsonResult['status'] = 'ok';
		$jsonResult['survey'] = 1;

	}

}


if(isset($jsonArray->answer->phone)) {

	if($jsonArray->answer->phone == 1) $jsonArray->answer->phone = '-1';

	$sqlGetPhone = "
UPDATE mobileuser
SET phone = '".mysql_real_escape_string($jsonArray->answer->phone)."'
WHERE id = '"
		.mysql_real_escape_string($rowUser['id'])."' "
		;
	$resultPhone = mysql_query($sqlGetPhone);

	if($resultPhone) {
		$jsonResult['status'] = 'ok';
		$jsonResult['phone'] = $jsonArray->answer->phone;
	}

}


echo(json_encode(utf8_converter($jsonResult)));





