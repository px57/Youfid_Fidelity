<?php

	require_once("Logger.class.php");

	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

	/// table name
	$tbl_name="marchand_has_mobileuser";
	require_once('dbLogInfo.php');
	require_once('utils.php');
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	$logger->log('debug', 'getLocalisedUsersList', "Request=" . $json, Logger::GRAN_MONTH);

	$error = false;
	$errorMsg = "";
	$tabIndex = 0;
	$user_tab = array();
	if ($jsonArray->merchant_id)
	{
		/// BOUTON JE SUIS LA
		$getTimeUser = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "' AND date_localisation > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
		$result = mysql_query($getTimeUser);
		if ($result == FALSE){
			$error = true;
			$errorMsg = "Error with the db::" . $sqlApp;
		}
		else {
			while ($idCustomerRow = mysql_fetch_array($result)) {
				$getClient = "Select * FROM mobileuser WHERE id = " . $idCustomerRow['mobileuser_id'];
				$resultUsser = mysql_query($getClient);
				if ($result == FALSE){
					$error = true;
					$errorMsg = "Error with the db::" . $sqlApp;
				}
				else {
				$resultUser = mysql_fetch_array($resultUsser);
				$user_tab[$tabIndex]['usr_id'] = $resultUser['id'];
				$user_tab[$tabIndex]['first_name'] = $resultUser['prenom'];
				$user_tab[$tabIndex]['last_name'] = $resultUser['nom'];
				$user_tab[$tabIndex]['picture'] = $resultUser['photo'];
				$tabIndex += 1;
				}
			}
		}
		//////////////////////////////////////
		// Geo loc

		$getMarchand = "SELECT * FROM marchand WHERE `id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";
		$resultMarchand = mysql_query($getMarchand);
		if ($resultMarchand == FALSE){
			$error = true;
			$errorMsg = "Error with the db::" . $getMarchand;
		}
		else {
			$marchandRow = mysql_fetch_array($resultMarchand);
			$getUser = "SELECT * FROM mobileuser";
			$result = mysql_query($getUser);
			if ($result == FALSE){
				$error = true;
				$errorMsg = "Error with the db::" . $getUser;
			}
			else {
				while ($idCustomerRow = mysql_fetch_array($result)){
					$getUser = "SELECT * FROM mobileuser WHERE `id` = '"
					. mysql_real_escape_string($idCustomerRow['id'])
					. "'";
					$resultUser = mysql_query($getUser);
					if ($resultUser == FALSE){
						$error = true;
						$errorMsg = "Error with the db::" . $getMarchand;
					}
					else {
					$resultUser = mysql_fetch_array($resultUser);
					if (distance((double)$resultUser['longitude'], (double)$resultUser['lattitude'], (double)$marchandRow['longitude'], (double)$marchandRow['latittude']) <= 0.3) {
						$test = 0;
						$pb = 0;

						while (count($user_tab) < $test && $user_tab[$test]){
							if ($resultUser['id'] == $user_tab[$test]['usr_id']) {
								$pb = 1;
								break;
							}
							$test += 1;
						}
						if ($pb == 0){
							$user_tab[$tabIndex]['usr_id'] = $resultUser['id'];
							$user_tab[$tabIndex]['first_name'] = $resultUser['prenom'];
							$user_tab[$tabIndex]['last_name'] = $resultUser['nom'];
							$user_tab[$tabIndex]['picture'] = $resultUser['photo'];
							$tabIndex += 1;
							}
						}
					}
				}
			}
		}

	}
	else {
		$error = true;
		$errorMsg = "Error with parameters";
	}


	if ($error == TRUE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult['status'] = "ok";

		$jsonResult['users'] = $user_tab;
		$jsonResult['accueil_marchand'] = '0';
		if ($marchandRow['is_accueil_client'] == '0')
			$jsonResult['accueil_marchand'] = '1';
	}

	//$logger->log('debug', 'getLocalisedUsersList', "Response=" . $jsonResult, Logger::GRAN_MONTH);

	echo(json_encode(array_map_utf8_encode($jsonResult)));

