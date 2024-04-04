<?php

	require_once("Logger.class.php");
	require_once '_security.php';

	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

	////////////////////////////////////////
	// DataBase Properties
	$tbl_merchant="marchand";
	$tbl_label="label";
	$tbl_gift="cadeau";
	$tbl_user= "mobileuser";
	require_once('dbLogInfo.php');
	require_once('utils.php');

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');
	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	if (isset($logger))
		$logger->log('debug', 'getMerchantDetails', "Request=" . $json, Logger::GRAN_MONTH);

	/**
	 * 	getMobileUser(Int)
	 *  Retourne la colonne du mobileuser associe a cet id
	 *
	 *  @param Int $usr_id id du mobileuser
	 */

	function getMobileUser($usr_id)
	{
		global $tbl_user, $logger;

		$sqlGetUser = "SELECT * FROM $tbl_user WHERE `id`='"
			. mysql_real_escape_string($usr_id)
			. "'";

		$result = mysql_query($sqlGetUser);

		if ($result && ($row = mysql_fetch_array($result)))
			return $row;

		return FALSE;
	}

	/// Fonction de login a loyalty
	function doLoyaltyLogin()
	{
		//global $logger;

		global $url_loyalty;
		$req_login = array(
        	"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        	"login" => "youfid",
        	"password" => "youfid"
			);

		$req_login = json_encode($req_login);

		$result = postRequest($url_loyalty . "services/user/login", $req_login);
		//$logger->log('debug', 'getMerchants_V2', "inLogin::response=" . $result);

		$youfid_access = json_decode($result);

		if (isset($youfid_access->error))
			$youfid_error = $youfid_access->error;
		else
			return FALSE;

		/// Si errorMessage == "OK" => on retourne un array youFidAccess
		if (isset($youfid_error->messages[0]) && $youfid_error->messages[0] == "OK")
			return $youfid_access;

		return FALSE;
	}

	/// Recupere sur loyalty les informations d'une application marchands. Retourne FALSE en cas d'erreur
	function doMobiuserAppsRequest($user, $wsAccess)
	{
		// Urls
		global $url_loyalty;
		$service_base_url = $url_loyalty . "services/";
		$get_application_service = "mobileuser/mobiuserapps";

		$req_get_application = array(
			"wsAccessPublicKey" => $wsAccess->wsAccessPublicKey,
			"wsAccessToken" => $wsAccess->wsAccessToken,
			"mobileUserPublicId" => $user['public_id']
		);

		$req_get_application_json = json_encode($req_get_application);

		$res_get_application_json = postRequest($service_base_url . $get_application_service, $req_get_application_json);

		$res_get_application = json_decode($res_get_application_json);

		//echo "<br/> get_application failure <br/> response=" . $res_get_application_json;

		if(!empty($res_get_application) && !empty($res_get_application->error) && $res_get_application->error->code == 0)
		{
			return $res_get_application->mobileUserApplications;
		}

		return (array());
	}

	$error = FALSE;
	$errorMsg = "";

	$merchantArray = array();
	$giftArray = array();

	//if (!isset($jsonArray->usr_id) || !isset($jsonArray->merchant_id))
	if (!isset($jsonArray->merchant_id))
	{
		$error = TRUE;
		$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	}
	else
	{
		$sqlMerchant = "SELECT * FROM $tbl_merchant WHERE `id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";

		$result = mysql_query($sqlMerchant);
		if ($result == FALSE)
		{
			$error = TRUE;
			$errorMsg = "Error with the db:: Rquest for merchant";
		}
		else
		{
			if (mysql_num_rows($result))
			{
				$merchantRow = mysql_fetch_array($result);

				$merchantArray['name'] = $merchantRow['name'];
				$merchantArray['logo'] = $merchantRow['logo'];
				$merchantArray['address'] = $merchantRow['address'];
				$merchantArray['latitude'] = $merchantRow['latittude'];
				$merchantArray['longitude'] = $merchantRow['longitude'];
				$merchantArray['phone'] = $merchantRow['phone'];
				$merchantArray['website'] = $merchantRow['site_internet'];
				$merchantArray['fb_page'] = $merchantRow['page_fb'];
				$merchantArray['bienvenue'] = $merchantRow['offre_bienvenue'];
				$merchantArray['horaires'] = $merchantRow['horaire'];
				$merchantArray['signal_is_on'] = $merchantRow['is_signalez_vous'];
				$merchantArray['address'] = $merchantRow['address'] . ", " . $merchantRow['zip_code'] . ", " .$merchantRow['city'];


				/// Recuperation de la label associée au marchand
				$sqllabel = "SELECT * FROM $tbl_label WHERE id = '"
					. mysql_real_escape_string($merchantRow['label_id'])
					. "'";
				$sqllabelResult = mysql_query($sqllabel);
				if (mysql_num_rows($sqllabelResult))
				{
					$labelRow = mysql_fetch_array($sqllabelResult);

					$merchantArray['type'] = $labelRow['nom'];

					/// Recuperation des points, appel a la fonction. En attendant, nbPoint = 0!


					/// Login loyalty
					if (($youfid_access = doLoyaltyLogin()) == FALSE)
					{
						$errorMsg = "Error: Login to loyalty server failure";
						$error = TRUE;
					}

					/// Si succes, get les infos de mobiuser
					if ($error == FALSE)
					{
						/* AJOUT ALEX 26/06/2013 */

						if (isset($jsonArray->qr_code))
						{
							// On récupère le user id à partir du qrcode
							$sqlRecup = "SELECT * FROM mobileuser WHERE qr_code = " .$jsonArray->qr_code;
							$resultRecup = mysql_query($sqlRecup);
							$rowRecup = mysql_fetch_assoc($resultRecup);

							if (!$rowRecup) {
							  $errorMsg = "Error: QR_code not existing";
							  $error = TRUE;
							}

							else $user = getMobileUser($rowRecup['id']);
						}
						else /* END AJOUT */if (!isset($jsonArray->usr_id))
							$user = -1;
						else
							$user = getMobileUser($jsonArray->usr_id);

						if ($user != FALSE && $user != -1)
							$result = doMobiuserAppsRequest($user, $youfid_access->wsAccess);

						if ($user == FALSE)
						{
							$errorMsg = "Error: Problem during getting point with Loyalty";
							$error = TRUE;
						}
						else
						{
							if ($user != -1)
							{
								for ($index = 0; isset($result[$index]); $index += 1)
								{
									if ($merchantRow['application_id'] == $result[$index]->application->publicId)
										$merchantArray['nb_pts'] = strval($result[$index]->totalPoints);
								}
								if (!isset($merchantArray['nb_pts']))
									$merchantArray['nb_pts'] = strval(0);

								$merchantArray['nb_pts'] = strval(max(0, $merchantArray['nb_pts']));

							}
						}

					}

					/// Recuperation des cadeaux associés a ce marchand
					$sqlGift = "SELECT * FROM `$tbl_gift` WHERE marchand_id = '"
						. mysql_real_escape_string($jsonArray->merchant_id)
						. "' ORDER BY `cout` ASC";

					$giftResult = mysql_query($sqlGift);
					if ($giftResult == FALSE)
					{
						$error = TRUE;
						$errorMsg = "Error with the db :: Gift request";
					}
					else
					{

							$index = 0;
							while ($giftRow = mysql_fetch_array($giftResult))
							{
								$gift = array();

								$gift['id'] = $giftRow['id'];
								$gift['cost'] = $giftRow['cout'];
								$gift['name'] = $giftRow['nom'];

								$giftArray[$index] = $gift;
								$index += 1;
							}

					}
				}
				else
				{
					$error = TRUE;
					$errorMsg = "Error: No Label define for the merchant";
				}

			}
			else
			{
				$error = TRUE;
				$errorMsg = "Error: no Merchant matching with the merchant_id:" . $jsonArray->merchant_id . ".";
			}
		}

	}

	$jsonResult = array();

	if ($error == TRUE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult = $merchantArray;
		$jsonResult['products'] = $giftArray;
		$jsonResult['status'] = "ok";
	}

	if (isset($logger))
		$logger->log('debug', 'getMerchantDetails', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);

	echo(json_encode( $jsonResult));
