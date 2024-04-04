<?php

	require_once("Logger.class.php");

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
				$merchantArray['address'] = $merchantRow['address'] . ", " . $merchantRow['zip_code'] . ", " .$merchantRow['city'];

				if($merchantRow['is_accueil_client'] == "0")$merchantArray['attribution_pts'] = "1";
				if($merchantRow['is_accueil_client'] == "1")$merchantArray['attribution_pts'] = "0";

				if($merchantRow['is_pin_marchand']=="1") $merchantArray['pin'] = $merchantRow['pin_code'];

				//Récuperation des images à afficher dans le caroussel du home screen

				$merchantArray['img_adv_1'] = $merchantRow['img_adv_1'];
				$merchantArray['img_adv_2'] = $merchantRow['img_adv_2'];
				$merchantArray['img_adv_3'] = $merchantRow['img_adv_3'];
				$merchantArray['img_adv_4'] = $merchantRow['img_adv_4'];
				$merchantArray['img_adv_5'] = $merchantRow['img_adv_5'];
				$merchantArray['img_adv_6'] = $merchantRow['img_adv_6'];
				$merchantArray['img_adv_7'] = $merchantRow['img_adv_7'];
				$merchantArray['img_adv_8'] = $merchantRow['img_adv_8'];
				$merchantArray['img_adv_9'] = $merchantRow['img_adv_9'];
				$merchantArray['img_adv_10'] = $merchantRow['img_adv_10'];

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

				$merchantArray['products'] = $giftArray;
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
		$jsonResult['status'] = "ok";
	}

	if (isset($logger))
		$logger->log('debug', 'getMerchantDetails', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);

	echo(json_encode($jsonResult));
