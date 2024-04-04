<?php

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit;
}

// BE CAREFULL : LOGS CAN NOTBE INCLUDED IN PREPROD.

require_once("../../lib/Logger.class.php");
//require_once('Logger.class.php');

require_once '../../lib/db_functions.php';
require_once 'utils.php';

yf_security_log_event('getanswerv2');


if (!isset($logger))
    $logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

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

$youfidDb = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$json = file_get_contents('php://input');
$headers = array();
if(function_exists('apache_request_headers')) {
  $headers = apache_request_headers();
} else {
  $headers = array(
    'Authorization' => $_SERVER['HTTP_AUTHORIZATION']
  );
}

$authorization = checkRequestAuthorization($youfidDb, $headers, $json);

$jsonArray = json_decode($json);

if(isset($jsonArray->merchant_id)) {
  $query = $youfidDb->prepare("SELECT * FROM marchand WHERE `id` = :id");
  $query->execute(array(
    'id' => $jsonArray->merchant_id
  ));

  $rowMarchand = false;
  if($query->rowCount() === 1) {
    $rowMarchand = $query->fetch(PDO::FETCH_ASSOC);
    if(isSecurityActivated($rowMarchand) && (!$authorization || !$authorization->granted || $rowMarchand['id'] != $authorization->merchantId)) {
      $error = TRUE;
      $errorMsg = 'Not authorized: ' . $authorization->error;
      // header("HTTP/1.1 401 Unauthorized");
      die(json_encode(
        array(
          'status' => "error",
          'message' => $errorMsg,
          'code' => 'ERR0401'
        )
      ));
    }
  } else {
    die(json_encode(array(
      'status' => 'error',
      'message' => 'Merchant not found',
      'code' => 'ERR0400'
    )));
  }
} else {
  die(json_encode(array(
    'status' => 'error',
    'message' => 'Merchant id is mandatory ',
    'code' => 'ERR0400'
  )));
}

////////////////////////////////////////
// DataBase connection
mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

$logger->log('debug', 'getAnswer', "Request=" . print_r($jsonArray, true), Logger::GRAN_MONTH);

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

if(isset($jsonArray->answer->survey)) {

  $logger->log('debug', 'getAnswer', 'Saving survey note', Logger::GRAN_MONTH);

  $questionId = intval($jsonArray->answer->survey);
  $merchandId = intval($jsonArray->answer->marchandId ? $jsonArray->answer->marchandId : $rowMarchand['id']);
  $userId = intval($rowUser['id']);

  $values = array(
    $questionId,
    $merchandId,
    $userId,
    intval($jsonArray->answer->note),
    intval($rowMarchand['supermarchand_id']) ? intval($rowMarchand['supermarchand_id']) : 'NULL'
  );

  // Checks if survey exists
  $surveyRes = mysql_query("SELECT * FROM survey_results WHERE question_id = " . $questionId . " AND  marchand_id = " . $merchandId . " AND user_id = " . $userId);
  if(mysql_num_rows($surveyRes) > 0) {
    $rowSurvey = mysql_fetch_array($surveyRes);
    if(empty($rowSurvey['result'])) {
      $resultAnswer = mysql_query("UPDATE survey_results SET result = " . intval($jsonArray->answer->note) . ", added = NOW() WHERE id = " . $rowSurvey['id']);

      $logger->log('debug', 'getAnswer', 'Update survey note res: ' . print_r($resultAnswer, true), Logger::GRAN_MONTH);
      if($resultAnswer) {
        $jsonResult['status'] = 'ok';
        $jsonResult['survey'] = 1;
      }
    } else {
      $jsonResult['status'] = 'ok';
      $jsonResult['survey'] = 1;
    }
  } else {
    $sqlGetSurvey = "INSERT INTO survey_results (question_id, marchand_id, user_id, result, supermarchand_id, added) VALUES ( ".implode(',', $values).", NOW())";

    $resultAnswer = mysql_query($sqlGetSurvey);

    if($resultAnswer) {
      $jsonResult['status'] = 'ok';
      $jsonResult['survey'] = 1;
    }
  }
  $logger->log('debug', 'getAnswer', 'Survey note saved', Logger::GRAN_MONTH);
}

if(isset($jsonArray->answer->comment) && !empty($jsonArray->answer->comment)) {

  $logger->log('debug', 'getAnswer', 'Saving survey comment', Logger::GRAN_MONTH);

  $values = array(
    "'".mysql_real_escape_string($jsonArray->answer->comment)."'",
    intval($rowMarchand['id']),
    intval($rowUser['id']),
    intval($jsonArray->answer->note),
    intval($rowMarchand['supermarchand_id']) ? intval($rowMarchand['supermarchand_id']) : 'NULL'
  );

  $sqlGetSurvey = "
INSERT INTO survey_notes
(note, marchand_id, user_id, result, supermarchand_id, added, passdate) VALUES ( ".implode(',', $values).", NOW(), NOW())";

  $resultAnswer = mysql_query($sqlGetSurvey);

  if($resultAnswer) {
    $jsonResult['status'] = 'ok';
    $jsonResult['survey'] = 1;
  }

  $logger->log('debug', 'getAnswer', 'Survey comment saved', Logger::GRAN_MONTH);
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
?>
