<?php

function utf8_converter($array)
{
	array_walk_recursive(
		$array,
		function (&$item, $key) {
			if (!mb_detect_encoding($item, 'utf-8', true)) {
				$item = utf8_encode($item);
			}
		}
	);

	return $array;
}

require_once("Logger.class.php");

if (!isset($logger))
	$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

////////////////////////////////////////
// DataBase Properties
////////////////////////////////////////

require_once('dbLogInfo.php');
require_once('utils.php');

$authorization = null;

$json = file_get_contents('php://input');

$youfidDb = new PDO($youfid_pdo_connection_string, $username, $password);
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

if (isset($logger))
	$logger->log('debug', 'getMerchantDetails', "Request=" . $json, Logger::GRAN_MONTH);

$error = FALSE;
$errorMsg = "";
$errorCode = '';

$merchantArray = array();
$giftArray = array();

//if (!isset($jsonArray->usr_id) || !isset($jsonArray->merchant_id))
if (!isset($jsonArray->merchant_id)) {
	$error = TRUE;
	$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	$errorCode = 'ERR0404';
} else {
	$query = $youfidDb->prepare('SELECT * FROM marchand WHERE id = :id');
	$query->execute(array(
		'id' => $jsonArray->merchant_id
	));
	
	$merchantRow = fetchOne($query, PDO::FETCH_ASSOC);
	// $result = mysql_query($sqlMerchant);
	if ($merchantRow === false) {
		$error = TRUE;
		$errorMsg = "Error: no Merchant matching with the merchant_id:" . $jsonArray->merchant_id . ".";
		$errorCode = 'ERR0404';
	} else {
		if(isSecurityActivated($merchantRow) && (!$authorization || !$authorization->granted || $merchantRow['id'] != $authorization->merchantId)) {
			$error = TRUE;
			$errorMsg = 'Not authorized: ' . $authorization->error;
			$errorCode = 'ERR0401';
		} else {
			$merchantArray['name'] = $merchantRow['name'];
			$merchantArray['logo'] = $merchantRow['logo'];

			$merchantArray['address'] = $merchantRow['address'] . ", " . $merchantRow['zip_code'] . ", " .$merchantRow['city'];

			if($merchantRow['is_accueil_client'] == "0") {
				$merchantArray['attribution_pts'] = "1";
			}

			if($merchantRow['is_accueil_client'] == "1") {
				$merchantArray['attribution_pts'] = "0";
			}

			$merchantArray['pin'] = $merchantRow['pin_code'];
			$merchantArray['is_pin_marchand'] = $merchantRow['is_pin_marchand'];

			$merchantArray['is_promo'] = $merchantRow['is_promo'];

			$merchantArray['survey_desk'] = $merchantRow['is_promo'];

			if($merchantRow['is_desk']==1 ) {
				$merchantArray['is_desk'] = 1;
				$merchantArray['categories'] = array();

				$query = $youfidDb->prepare('SELECT * FROM desk_categories WHERE `supermarchand_id` = :superMerchantId OR marchand_id = :merchantId');
				$query->execute(array(
					"superMerchantId" => $merchantRow['supermarchand_id'],
					"merchantId" => $merchantRow['id']
				));

				// $sqlCategories = "SELECT * FROM desk_categories WHERE `supermarchand_id` = ".$merchantRow['supermarchand_id']." OR marchand_id =".$merchantRow['id'];
				// $categories = mysql_query($sqlCategories);
				$categories = fetchCollection($query, PDO::FETCH_ASSOC);
				$deskIndex = 0;

				foreach($$categories as $category) {

					$merchantArray['categories'][] = array('category_id' => $category['id'], 'name' => $category['name'], 'products'=>array());

					$query = $youfidDb->prepare("SELECT * FROM desk_products WHERE `category_id` = :id");
					$query->execute(array(
						"id" => $category['id']
					));

					// $sqlProducts = "SELECT * FROM desk_products WHERE `category_id` = ".$category['id'];
					$products = fetchCollection($query, PDO::FETCH_ASSOC);
					foreach($products as $product) {
						$merchantArray['categories'][$deskIndex]['products'][] = array('product_id' => $product['id'], 'name' => $product['name']);
					}

					$deskIndex++;
				}
			}

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
			$query = $youfidDb->prepare("SELECT * FROM cadeau WHERE marchand_id = :id ORDER BY `cout` ASC");
			$query->execute(array(
				"id" => $jsonArray->merchant_id
			));
	
			$giftResult = fetchCollection($query, PDO::FETCH_ASSOC);

			$i = 0;
			foreach($giftResult as $giftRow) {
				$gift = array(
					'id' => $giftRow['id'],
					'cost' => $giftRow['cout'],
					'name' => $giftRow['nom'],
					'type' => $giftRow['type']
				);

				$giftArray[$i] = $gift;
				$i++;
			}

			$merchantArray['products'] = $giftArray;

			if(!empty($merchantRow['loyalty_program_id'])) {
				// $lp_query = mysql_query("SELECT * FROM loyalty_program WHERE id = " . $merchantRow['loyalty_program_id']);
				$query = $youfidDb->prepare("SELECT * FROM loyalty_program WHERE id = :id");
				$query->execute(array(
					"id" => $merchantRow['loyalty_program_id']
				));

				$program = fetchOne($query, PDO::FETCH_ASSOC);
				if($program) {
					$merchantArray['loyalty_program'] = array(
						"id" => $program['id'],
						"name" => $program['name'],
						"program_type" => $program['program_type'],
						"validity" => $program['validity']
					);
				}
			}
		}
	}
}

$jsonResult = array();

if ($error == TRUE) {
	$jsonResult['status'] = "error";
	$jsonResult['message'] = $errorMsg;
	$jsonResult['code'] = $errorCode;
} else {
	$jsonResult = $merchantArray;
	$jsonResult['status'] = "ok";
}

$json = json_encode(array_map_utf8_encode($jsonResult));
if (isset($logger))
	$logger->log('debug', 'getMerchantDetails', "Response=" . $json, Logger::GRAN_MONTH);

echo($json);

