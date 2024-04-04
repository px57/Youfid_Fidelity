<?php
//require_once("Logger.class.php");
require_once 'utils.php';
//require_once('Logger.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
require_once '../../lib/db_functions.php';

if (!isset($logger))
	$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

function doLog($message) {
	global $logger;

	if (isset($logger))
		$logger->log('debug', 'checkScanUser', $message, Logger::GRAN_MONTH);
}

//// Login ////
$login_url = $url_loyalty . 'services/user/login';
$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
$result =  postRequest($login_url, $json_login);
$loginResult = json_decode($result, true);
///////////

function ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray) {
	global $url_loyalty;

 	if($rowMarchand['is_accueil_client'] == '1') {
		$accueil_json = '{
			"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
			"wsAccessToken" : "' . $loginResult['wsAccess']['wsAccessToken'] . '",
			"mobileUserPublicId" : "'. $rowUser['public_id']  . '",
			"applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
			"points" : "' . $rowMarchand['points_for_accueil']  . '"
		}';

		$accueil_url = $url_loyalty . "services/transaction/addpoints";
		$result_accueil = postRequest($accueil_url, $accueil_json);
	} else if (isset($jsonArray->amount)){
		$transaction_url = $url_loyalty . "services/transaction";
		$transaction_json = '{
					"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
					"wsAccessToken" : "' . $loginResult['wsAccess']['wsAccessToken'] . '",
					"mobileUserPublicId" : "'. $rowUser['public_id']  . '",
					"applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
					"amount" : "' . $jsonArray->amount  . '"
					}';
		$result_accueil = postRequest($transaction_url, $transaction_json);
	} else {
		$toadd = $rowMarchand['points_for_accueil'];
		if ($toadd == 0)
			$toadd = 5;
		$accueil_json = '{
					"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
					"wsAccessToken" : "' . $loginResult['wsAccess']['wsAccessToken'] . '",
					"mobileUserPublicId" : "'. $rowUser['public_id']  . '",
					"applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
					"points" : ' . $toadd  . '
					}';
		$accueil_url = $url_loyalty . "services/transaction/addpoints";
		$result_accueil = postRequest($accueil_url, $accueil_json);
	}

	return $result_accueil;
 }

function getUserId($qrcode){
	$sqlGetMerchant = "SELECT * FROM mobileuser WHERE `qr_code` = '"
			. mysql_real_escape_string($qrcode)
			. "'";
	$result = mysql_query($sqlGetMerchant);
	if ($result == FALSE || mysql_num_rows($result) == 0){
		return ('0');
	}
	$row = mysql_fetch_array($result);
	return ($row['id']);

}


function getMerchantId($appid){
	$sqlGetCustomer = "SELECT * FROM marchand WHERE `application_id` = '"
			. mysql_real_escape_string($appid)
			. "'";
	$result = mysql_query($sqlGetCustomer);
	if ($result == FALSE){
		return ('0');
	}
	$row = mysql_fetch_array($result);
	return ($row['id']);

}




//require_once 'utils.php';
$login_url = $url_loyalty . 'services/user/login';
$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
$result =  postRequest($login_url, $json_login);
$loginResult = json_decode($result, true);

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

	// Get app id
		$sqlGetMarchand = "SELECT * FROM marchand WHERE `id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";
		$resultMarchand = mysql_query($sqlGetMarchand);
		$rowMarchand = mysql_fetch_array($resultMarchand);
		//////////////



	// Si le marchand n'est pas activé
	if($rowMarchand['is_active']==0) {
		$error = true;
		$errorMsg = "Compte marchand desactive";
	}

	else if ((isset($jsonArray->qr_code) || isset($jsonArray->usr_id)) && isset($jsonArray->merchant_id) && $maxChecked == 0)
	{
		if (isset($jsonArray->qr_code)){
			$usr_id = getUserId($jsonArray->qr_code);
		}
		else {
			$usr_id = $jsonArray->usr_id;
		}
		if ($usr_id == '0'){
			$isReg = false;
			$error = true;
		}
		else {

			/// OLD
			/*$yesterday = date('Y-m-d', mktime()-86400) . ' 23:59:59';
			$tomorrow = date('Y-m-d',mktime()+86400) . ' 00:00:01';
			$nbScan = "SELECT * FROM transaction WHERE transaction_date > '"
			. mysql_real_escape_string($yesterday)
			. "' && `transaction_date` < '"
			. mysql_real_escape_string($tomorrow)
			. "' && `mobileuser_id` = '"
			. mysql_real_escape_string($usr_id)
			. "' && `marchand_id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "' && `value` > 0";*/

			/// NEW

			$nbScan = "SELECT 'transaction_date' FROM `transaction` WHERE"
				. " UNIX_TIMESTAMP(transaction_date) BETWEEN UNIX_TIMESTAMP(CURDATE())"
				. " AND UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL + 86399 SECOND))"
				. " AND `mobileuser_id` = '"
				. mysql_real_escape_string($usr_id)
				. "' AND `marchand_id` = '"
				. mysql_real_escape_string($jsonArray->merchant_id)
				. "' AND `value` > 0";

			doLog("Query::" . $nbScan);

			$maxChecked = 0;
			$resultScan = mysql_query($nbScan);
			$totalScan = mysql_num_rows($resultScan);

			doLog("TotalScan::" . $totalScan . " maxscan::" . $rowMarchand['max_scan']);
			$maxScan = intval($rowMarchand['max_scan']);
			if ($maxScan == 0)
				$maxScan = 1;
			if ($totalScan >= $maxScan){
				$error = true;
				$maxChecked = 1;
			}

			else {
					$merchant_id = $jsonArray->merchant_id;
					 subscribe_user_to_sm($merchant_id, $usr_id);
					// VERIF SI LIEN DEJA EFFECTUE
					$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
			. mysql_real_escape_string($usr_id)
			. "' && `marchand_id` = '"
			. mysql_real_escape_string($merchant_id)
			. "'";
		$result = mysql_query($sqlGetCustomer);



		/// Get public user ID
		$sqlGetUser = "SELECT * FROM mobileuser WHERE `id` = '"
					. mysql_real_escape_string($usr_id)
					. "'";
		$result2 = mysql_query($sqlGetUser);
		$rowUser = mysql_fetch_array($result2);


		//////////////
		//
		// FIRST MERCHANT
		//
		//////////////
		if (!(isset($rowUser['first_merchant'])) || $rowUser['first_merchant'] == ""){
			$ins = 'UPDATE mobileuser SET first_merchant="' . $rowMarchand['name'] .'" WHERE id = ' . $rowUser['id'];
			mysql_query($ins);
		}





		// TEST IF USER ON FID SERV
		$test_url = $url_loyalty . 'services/user/login';
		$json_test = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
			. $loginResult['wsAccess']['wsAccessToken']  . '", "mobileUserPublicId":"'
			.  $rowUser['public_id'] . '", "applicationPublicId":"'. $rowMarchand['application_id'] . '", "points" : "5"}';
		$resultPts =  postRequest($test_url, $json_test);
		$testResult = json_decode($result, true);
		if ($testResult['mobileUserApplication'] == NULL) {
			// ajout s

			//inscription
			$json_insri = '{
					"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
					"wsAccessToken" : "' . $loginResult['wsAccess']['wsAccessToken'] . '",
					"mobileUserPublicId" : "'. $rowUser['public_id']  . '",
					"applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
					"points" : 0
					}';
			$inscri_url = $url_loyalty . "services/mobileuser";
			$result_inscri =  postRequest($inscri_url, $json_insri);
			$inscriResult = json_decode($result_inscri, true);
		}


		if ($result == false)
		{
			$error = true;
			$errorMsg = "Error with DB";
		}
		else
		{

			////// Add ALEX MAJ mobileuser->nb_use //////

			$updateNbUse = "UPDATE LOW_PRIORITY mobileuser SET nb_use=nb_use+1 WHERE `id` = '"
						. mysql_real_escape_string($usr_id)
						. "'";
			$resultUpdateNbUse = mysql_query($updateNbUse);

			/////////////////////////////////////////////

			$rowNb = mysql_num_rows($result);
			if ($rowNb == 0) // Relation jamais créée, donc création.
			{
				$createRelation = "INSERT INTO  $tbl_name SET marchand_id='"
				. mysql_real_escape_string($merchant_id)
				. "', mobileuser_id='"
				. mysql_real_escape_string($usr_id)
				. "', nb_use='"
				. mysql_real_escape_string("1")
				. "'";
				$resultInsert = mysql_query($createRelation);
				if ($resultInsert == FALSE){
					$error = true;
					$errorMsg = "Error with the db::" . $sqlApp;
				}
				else {

					$sqlGetSupa = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($usr_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "'";
					$resultSupa = mysql_query($sqlGetSupa);
					$rowSupa = mysql_fetch_array($resultSupa);
					$newSupaUse =  intval($rowSupa['nb_use']) + 1 ;
					$updateUse  = "UPDATE $tbl_name SET nb_use=$newSupaUse WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($usr_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "'";
					$resultUpdate = mysql_query($updateUse);


					$resultAdd = ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray);
					$points = json_decode($resultAdd, TRUE);
					$createHisto = "INSERT DELAYED INTO authentification SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', authent_date=NOW()";
					$resultInsert = mysql_query($createHisto);
				}
			}
			else // Relation existante, ajout des points
			{
				//ajout();
				$rowLink = mysql_fetch_array($result);
				$pastNbUse = intval($rowLink['nb_use']);
				$newNbUse = $pastNbUse + 1;
				///SUPERMARHCND
				if (intval($rowMarchand['supermarchand_id']) >= 1)
				{
					$sqlGetSupa = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($usr_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "'";
					$resultSupa = mysql_query($sqlGetSupa);
					$rowSupa = mysql_fetch_array($resultSupa);
					$newSupaUse =  intval($rowSupa['nb_use']) + 1 ;
					$updateUse  = "UPDATE $tbl_name SET nb_use=$newSupaUse WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($usr_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "'";
					$resultUpdate = mysql_query($updateUse);
				}
				$updateUse  = "UPDATE $tbl_name SET nb_use=$newNbUse WHERE `mobileuser_id` = '"
			. mysql_real_escape_string($usr_id)
			. "' && `marchand_id` = '"
			. mysql_real_escape_string($merchant_id)
			. "'";
				$resultUpdate = mysql_query($updateUse);
				if ($resultUpdate == FALSE){
					$error = true;
					$errorMsg = "Error with the db::" . $sqlApp;
				}
				else {
					$resultAdd = ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray);
					$points = json_decode($resultAdd, TRUE);
					$createHisto = "INSERT DELAYED INTO  authentification SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', authent_date=NOW()";
					$resultInsert = mysql_query($createHisto);
					if (intval($rowMarchand['supermarchand_id']) >= 1)
					{
						$createHisto = "INSERT DELAYED INTO  authentification SET marchand_id='"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "', mobileuser_id='"
						. mysql_real_escape_string($usr_id)
						. "', authent_date=NOW()";
						$resultInsert = mysql_query($createHisto);
					}

				}
					}
			}
		}
	}
	}
	else {
		$error = true;
		$errorMsg = "Bad Parameters";
	}

	if ($error == true){
		if ($isReg == true)	{
		$status = "error";
		}

		else {
		$status = "register";
		}

		if ($maxChecked == 1) {
			$merchant_id = $jsonArray->merchant_id;
			$max = '1';
			$errorMsg = "";
			$status = "ok";
			$jsonResult['usr_id'] = $usr_id;
			/// Get public user ID
			$sqlGetUser = "SELECT * FROM mobileuser WHERE `id` = '"
					. mysql_real_escape_string($usr_id)
					. "'";
			$result2 = mysql_query($sqlGetUser);
			$rowUser = mysql_fetch_array($result2);

			// Get app id
			$sqlGetMarchand = "SELECT * FROM marchand WHERE `id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";
			$resultMarchand = mysql_query($sqlGetMarchand);
			$rowMarchand = mysql_fetch_array($resultMarchand);
		///////////

			$jsonResult['first_name'] = $rowUser['prenom'];
			$jsonResult['last_name'] = $rowUser['nom'];
			$jsonResult['won_pts'] = '0';
			$jsonResult['maximum_scans_reached'] = $max;

			$pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
			$json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
			. $loginResult['wsAccess']['wsAccessToken']  . '", "mobileUserPublicId":"'
			.  $rowUser['public_id'] . '", "applicationPublicId":"'. $rowMarchand['application_id'] . '"}';
			$resultPts =  postRequest($pts_url, $json_pts);
			$ptsResult = json_decode($resultPts, true);
			$jsonResult['total_pts'] = $ptsResult['mobileUserApplication']['totalPoints'];
			$jsonResult['pin_user_is_active'] = $rowUser['is_pin_active'];
			$jsonResult['pin_user'] = $rowUser['pin_code'];
			$jsonResult['pin_merchant_is_active'] = $rowMarchand['is_pin_marchand'];
			$jsonResult['pin_merchant'] = $rowMarchand['pin_code'];
			// ????? //
			$createHisto2 = "INSERT DELAYED INTO  authentification SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', authent_date=NOW()";
			$resultInsert = mysql_query($createHisto2);
				if (intval($rowMarchand['supermarchand_id']) >= 1)
					{
						$createHisto = "INSERT DELAYED INTO  authentification SET marchand_id='"
						. mysql_real_escape_string($rowMarchand['supermarchand_id'])
						. "', mobileuser_id='"
						. mysql_real_escape_string($usr_id)
						. "', authent_date=NOW()";
						$resultInsert = mysql_query($createHisto);
					}
		}
	}
	else
	{
		$status = "ok";
		$jsonResult['usr_id'] = $usr_id;
		$jsonResult['first_name'] = $rowUser['prenom'];
		$jsonResult['last_name'] = $rowUser['nom'];
		if (isset($points['transaction']['point'])) {
			$jsonResult['won_pts'] = $points['transaction']['point'];

		}
		else
			$jsonResult['won_pts'] = '0';
		if (isset($points['mobileUserApplication']['totalPoints']))
			$jsonResult['total_pts'] = $points['mobileUserApplication']['totalPoints'];
		else {
			$pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
			$json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
			. $loginResult['wsAccess']['wsAccessToken']  . '", "mobileUserPublicId":"'
			.  $rowUser['public_id'] . '", "applicationPublicId":"'. $rowMarchand['application_id'] . '"}';
			$resultPts =  postRequest($pts_url, $json_pts);
			$ptsResult = json_decode($resultPts, true);
			$jsonResult['total_pts'] = $ptsResult['mobileUserApplication']['totalPoints'];
		}
		$jsonResult['pin_user_is_active'] = $rowUser['is_pin_active'];
		$jsonResult['pin_user'] = $rowUser['pin_code'];
		$jsonResult['pin_merchant_is_active'] = $rowMarchand['is_pin_marchand'];
		$jsonResult['pin_merchant'] = $rowMarchand['pin_code'];


		if ($jsonResult['won_pts'] > 0) {
			$createHisto2 = "INSERT DELAYED INTO  transaction SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', value='"
					. mysql_real_escape_string($points['transaction']['point'])
					. "', id_loyalty='"
					. mysql_real_escape_string($points['transaction']['publicId'])
					. "', amount='"
					. mysql_real_escape_string($jsonArray->amount)
					. "', transaction_date=NOW()";
			$resultInsert = mysql_query($createHisto2);
			$createHisto3= "INSERT DELAYED INTO  message SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', type='"
					. mysql_real_escape_string('recu')
					. "', points='"
					. mysql_real_escape_string($jsonResult['won_pts'])
					. "', message='"
					. mysql_real_escape_string("points recus")
					. "', start_date=NOW()"
					. ", is_validated='"
					. mysql_real_escape_string("1")
					. "'";
			$resultInsert = mysql_query($createHisto3);
			$message_id = mysql_insert_id();
			$linkMsg = "INSERT DELAYED INTO  message_has_mobileuser SET message_id='"
					. mysql_real_escape_string($message_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', date_creation=NOW()";
			$resultInsert = mysql_query($linkMsg);
		}

	}


	$jsonResult['status'] = $status;
	$jsonResult['message'] = $errorMsg;

	$logger->log('debug', 'checkScanUser', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);

	echo(json_encode(array_map_utf8_encode($jsonResult)));


