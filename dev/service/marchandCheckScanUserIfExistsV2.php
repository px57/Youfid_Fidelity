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

//$logger->log('debug', 'checkScanUser', "Request=" . $json, Logger::GRAN_MONTH);

$jsonResult = [];

$jsonResult['is_user'] = 0;
$jsonResult['questions'] = 0;
$jsonResult['survey'] = 0;
$jsonResult['ask_phone'] = 1;
$jsonResult['promo'] = 0;


/*
 * Check if mobileuser exists
 */
if(isset($jsonArray->qr_code)) {
	$sqlGetUser = "
SELECT mobileuser.id, mobileuser.phone
FROM mobileuser
WHERE `qr_code` = '"
			. mysql_real_escape_string($jsonArray->qr_code)
			. "'";
	$resultUser = mysql_query($sqlGetUser);
	$rowUser = mysql_fetch_array($resultUser);
	

}

if(isset($jsonArray->merchant_id)) {
	$sqlGetMarchand = "
SELECT *
FROM marchand
WHERE `id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";
	$resultMarchand = mysql_query($sqlGetMarchand);
	$rowMarchand = mysql_fetch_array($resultMarchand);
	

}

if($rowUser && $rowMarchand) {
	$jsonResult['is_user'] = 1;
}



/*
 * Check if marchand_has_mobileuser exists
 */


if(isset($rowMarchand) && isset($rowUser)) {
	$sqlGetRelation = "
SELECT DATE(creation_date)
FROM marchand_has_mobileuser
WHERE `mobileuser_id` = '"
			. mysql_real_escape_string($rowUser['id'])
			. "'
AND `marchand_id` = '"
			. mysql_real_escape_string($rowMarchand['id'])
			. "'

		";
	$resultRelation = mysql_query($sqlGetRelation);
	$rowRelation = mysql_fetch_array($resultRelation);

}


if($rowMarchand['ask_phone'] && strlen($rowUser['phone']) > 3 || $rowUser['phone'] == '-1') {
	$jsonResult['ask_phone'] = 0;
}


/*
 * Check if UBERISATION exists
 */


if(isset($rowMarchand) && isset($rowUser) && $rowMarchand['survey_desk']) {
	$sqlGetQuestions = "
SELECT sq.id, sq.question, sr.result
FROM survey_questions sq
LEFT JOIN survey_results sr ON sr.question_id = sq.id AND sr.user_id = '".$rowUser['id']."'
WHERE sq.marchand_id = '"
		. mysql_real_escape_string($rowMarchand['id'])
		. "'";
	$resultQuestion = mysql_query($sqlGetQuestions);

	while($rowQuestion = mysql_fetch_array($resultQuestion))
	{
		if($rowQuestion['result'] == null) {
			$jsonResult['survey'] = array('id' => $rowQuestion['id'], 'question' => $rowQuestion['question']);
			break;
		}
	}

}


// Rotation enquete supermarchand

if(isset($rowMarchand) && isset($rowUser) && $rowMarchand['survey_desk'] && $rowMarchand['supermarchand_id']) {

	// Get dernier passage dans reseau du super


	$sqlGetDernierPassage = "
SELECT auth.marchand_id, auth.authent_date
FROM authentification auth
LEFT JOIN marchand m ON auth.marchand_id = m.id 
WHERE auth.mobileuser_id = '"
		. mysql_real_escape_string($rowUser['id'])
		. "' AND m.supermarchand_id = '"
		. mysql_real_escape_string($rowMarchand['supermarchand_id'])
		. "' ORDER BY auth.authent_date DESC LIMIT 1";
	$resultPassage = mysql_query($sqlGetDernierPassage);
	$rowPassage = mysql_fetch_array($resultPassage);


	// Get if results since last passage

	if($rowPassage) {

		$sqlGetResult = "
	SELECT * 
	FROM survey_results res
	WHERE res.user_id = '"
			. mysql_real_escape_string($rowUser['id'])
			. "' AND res.marchand_id = '"
			. mysql_real_escape_string($rowPassage['marchand_id'])
			. "' AND res.added >= '". mysql_real_escape_string($rowPassage['authent_date'])."'";
		$resultResult = mysql_query($sqlGetResult);
		$rowResult = mysql_fetch_array($resultResult);

	}


	if($rowPassage && !$rowResult) {

		// Get last shop name

		$sqlGetLastMarchand = "
SELECT id, `name` 
FROM marchand
WHERE id = '"
			. mysql_real_escape_string($rowPassage['marchand_id'])
			. "'";
		$resulLastMarchand = mysql_query($sqlGetLastMarchand);
		$rowLastMarchand = mysql_fetch_array($resulLastMarchand);

		// Get questions

		$sqlGetQuestions = "
SELECT sq.id, sq.question, sr.result
FROM survey_questions sq
LEFT JOIN survey_results sr ON sr.question_id = sq.id AND sr.user_id = '".$rowUser['id']."' AND sr.added > '".mysql_real_escape_string($rowPassage['authent_date'])."'
WHERE sq.marchand_id = '"
			. mysql_real_escape_string($rowLastMarchand['id'])
			. "'";
		$resultQuestion = mysql_query($sqlGetQuestions);

		$questions = [];

		while($rowQ = mysql_fetch_array($resultQuestion))
		{
			$questions[] = $rowQ;
		}

		shuffle($questions);

		foreach($questions as $rowQuestion)
		{
			if($rowQuestion['result'] == null) {
				$jsonResult['survey'] = array('id' => $rowQuestion['id'], 'question' => $rowQuestion['question'], 'marchandName'=>$rowLastMarchand['name']);
				break;
			}
		}

	} else {
		$jsonResult['survey'] = 0;

	}


}




/*
 * Check if promo running
 */

if(isset($rowRelation) && isset($rowMarchand) && isset($rowUser) && $rowMarchand['is_promo']) {

	$sqlGetMes = "
SELECT *
FROM message
WHERE `type` = 'promo'
AND `marchand_id` = '"
			. mysql_real_escape_string($rowMarchand['id'])
			. "'

AND DATE(NOW()) BETWEEN `start_date` AND `finish_date`
	ORDER BY finish_date DESC
	LIMIT 1

";
	#AND DATE(NOW()) BETWEEN `start_date` AND `finish_date`
	#AND DATE(NOW()) BETWEEN '2003-01-01' AND '2016-12-12'
	#ORDER BY finish_date DESC
	#LIMIT 1


	$resultPromo = mysql_query($sqlGetMes);
	$rowPromo = mysql_fetch_array($resultPromo);
	if($rowPromo && $rowRelation['creation_date'] <= $rowPromo['start_date']) {
		$jsonResult['promo'] = $rowPromo;
	}

}

echo(json_encode(utf8_converter($jsonResult)));


?>





