<?php

// BE CAREFULL : LOGS CAN NOTBE INCLUDED IN PREPROD.

	//require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	//require_once('Logger.class.php');

	require_once '../../lib/db_functions.php';
	require_once 'utils.php';



$tbl_name="marchand_has_mobileuser";
require_once('dbLogInfo.php');


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

$logger->log('debug', 'checkScanUser', "Request=" . $json, Logger::GRAN_MONTH);

$jsonResult = [];

$jsonResult['is_user'] = 0;
$jsonResult['used'] = 0;


/*
 * Check if marchand exists
 */
if(isset($jsonArray->merchant_id)) {
    $sqlGetMarchand = "
SELECT id, supermarchand_id
FROM marchand
WHERE `id` = '"
        . mysql_real_escape_string($jsonArray->merchant_id)
        . "'";
    $resultMarchand = mysql_query($sqlGetMarchand);
    $rowMarchand = mysql_fetch_array($resultMarchand);
    if($rowMarchand) {
        $jsonResult['is_merchant'] = 1;
    }

}

/*
 * Check if mobileuser exists
 */
if(isset($jsonArray->qr_code)) {
	$sqlGetMobileUser = "
SELECT id
FROM mobileuser
WHERE `qr_code` = '"
			. mysql_real_escape_string($jsonArray->qr_code)
			. "'";
	$resultMobileUser = mysql_query($sqlGetMobileUser);
	$rowMobileUser = mysql_fetch_array($resultMobileUser);
	if($rowMobileUser) {
		$jsonResult['is_user'] = 1;
	}

}

/*
 * Check if message_has_mobileuser
 */

if(isset($rowMobileUser['id']) && isset($jsonArray->message_id)) {
    $sqlGetIsPromo = "
SELECT id
FROM message_has_mobileuser
WHERE `message_id` = '"
        . mysql_real_escape_string($jsonArray->message_id)
        . "'
 AND `mobileuser_id` = '"
        . mysql_real_escape_string($rowMobileUser['id'])
        . "'

			";
    $resultIsPromo = mysql_query($sqlGetIsPromo);
    $rowIsPromo = mysql_fetch_array($resultIsPromo);
    if($rowIsPromo) {
        $jsonResult['has_read'] = 1;

    } else {
        $sqlGetInsPromo = "
INSERT INTO message_has_mobileuser
SET `date_creation` = NOW(), `message_id` = '"
            . mysql_real_escape_string($jsonArray->message_id)
            . "'
  ,`mobileuser_id` = '"
            . mysql_real_escape_string($rowMobileUser['id'])
            . "'

			";
        $resultInsPromo = mysql_query($sqlGetInsPromo);
    }
}

/*
 * Add used in message_has_mobileuser
 */

if(isset($rowMobileUser['id']) && isset($jsonArray->message_id)) {
	$sqlGetPromo = "
UPDATE message_has_mobileuser
SET used = 1
WHERE `message_id` = '"
			. mysql_real_escape_string($jsonArray->message_id)
			. "'
 AND mobileuser_id = '"
			. mysql_real_escape_string($rowMobileUser['id'])
			. "'

			";
	$resultPromo = mysql_query($sqlGetPromo);
    if($resultPromo) {
        $jsonResult['used'] = 1;

    }
}

/*
 * Insert authentification
 */
if(isset($rowMobileUser['id']) && isset($jsonArray->merchant_id)) {

    $createHisto2 = "INSERT INTO  authentification SET marchand_id='"
        . mysql_real_escape_string($rowMarchand['id'])
        . "', mobileuser_id='"
        . mysql_real_escape_string($rowMobileUser['id'])
        . "', authent_date=NOW()";
    $resultInsert = mysql_query($createHisto2);
    if (intval($rowMarchand['supermarchand_id']) >= 1) {
        $createHisto = "INSERT INTO  authentification SET marchand_id='"
            . mysql_real_escape_string($rowMarchand['supermarchand_id'])
            . "', mobileuser_id='"
            . mysql_real_escape_string($rowMobileUser['id'])
            . "', authent_date=NOW()";
        $resultInsert = mysql_query($createHisto);
    }
}



echo(json_encode($jsonResult));





