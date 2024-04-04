<?php

	header('Access-Control-Allow-Headers: x-super-merchant');
	header('Access-Control-Allow-Headers: x-additional-merchants');

	//require_once("../../lib/Logger.class.php");
	require_once('dbLogInfo.php');
	require_once('utils.php');
	require_once("Logger.class.php");

	yf_security_log_event('merchants');

	$logger = new Logger("logs/");

	$superMerchantId = null;
	$superFilterQry = null;
	if(function_exists('apache_request_headers')) {
		$headers = apache_request_headers();
	} else {
		$headers = [];
		if (array_key_exists("x-super-merchant", $_SERVER)) {
			$headers = ["x-super-merchant" => $_SERVER["x-super-merchant"]];
		}
	}
	
	if(array_key_exists("x-super-merchant", $headers)) {
		$superMerchantId = $headers["x-super-merchant"];

		$superFilterQry = "(supermarchand_id = " . $superMerchantId;

		if(array_key_exists("x-additional-merchants", $headers)) {
			$additionalMerchants = $headers["x-additional-merchants"];

			if($additionalMerchants !== null && trim($additionalMerchants) !== "") {
				$superFilterQry .= " OR m.id IN (" . trim($additionalMerchants) . ")";
			}
		}

		$superFilterQry .= ")";
	}

	//$logger->log('debug', 'getMerchants', "Super merchant filter: " . $superFilterQry, Logger::GRAN_MONTH);

	////////////////////////////////////////
	// DataBase Properties
	$tbl_merchant="marchand";
	$tbl_mobileuser="mobileuser";
	$tbl_transaction="transaction";
	$tbl_merchant_has_mobileuser="marchand_has_mobileuser";

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	if(isset($jsonArray->order_by) and $jsonArray->order_by == 'points') {
		$jsonArray->is_having_points = 1;
	}

	if (isset($logger))
		$logger->log('debug', 'getMerchants', "Request::" . $json, Logger::GRAN_MONTH);

	//$error = FALSE;
	$errorMsg = "";

	$merchant_array = array();
	$array_sort = array();

	if (isset($jsonArray->usr_id) && $jsonArray->usr_id == "0")
		$error = getMerchantsList($jsonArray);
	else
		$error = getMerchants($jsonArray);

	if ($error == FALSE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult['status'] = "ok";
		$jsonResult['merchants'] = $merchant_array;
	}

	if (isset($logger))
		$logger->log('debug', 'getMerchants', "Response::" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);

	header('Content-Type: application/json');
	echo(json_encode( $jsonResult));

	function getMerchantLastUse($merchant_id, $mbuser_id)
	{
		global $tbl_transaction;

		$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($mbuser_id)
			. "' ORDER BY `transaction_date` DESC";

		$result = mysql_query($query);

		if ($result == FALSE || !mysql_num_rows($result))
			return "";

		$row = mysql_fetch_array($result);
		return $row['transaction_date'];
	}

	function getMerchantNbUse($merchant_id, $mbuser_id)
	{
		global $tbl_merchant_has_mobileuser;

		$query = "SELECT * FROM $tbl_merchant_has_mobileuser WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($mbuser_id)
			. "'";

		$result = mysql_query($query);

		if ($result == FALSE)
			return "0";

		if ($row = mysql_fetch_array($result))
			return strval($row['nb_use']);
		return "0";
	}

	/// Retourne tous les marchands sans tenir compte de nb_pts ou de location
	function getMerchantsList($jsonArray)
	{
		global $tbl_merchant, $errorMsg, $merchant_array;

		if(isset($jsonArray->latitude) && !empty($jsonArray->latitude) && isset($jsonArray->longitude) && !empty($jsonArray->longitude)) {
			$query = "SELECT *, GeoDistDiff('km', latittude, longitude, $jsonArray->latitude, $jsonArray->longitude) AS distance FROM $tbl_merchant WHERE is_active ='1'";
		} else {
			$query = "SELECT * FROM $tbl_merchant WHERE is_active ='1'";
		}

		if(isset($jsonArray->supermarchand_id))
			$query = "$query AND supermarchand_id=" . intval($supermarchand_id);

		if(isset($jsonArray->search) && !empty($jsonArray->search)) {
			$query = "$query AND name like '%" . mysql_escape_string($jsonArray->search) ."%'";
		}

		if(isset($jsonArray->offset) && isset($jsonArray->nb_merchants)) {
			$query = "$query LIMIT " . $jsonArray->offset . ", " . $jsonArray->nb_merchants;
		}

		$result = mysql_query($query);

		if ($result)
		{
			while ($row = mysql_fetch_array($result))
			{
				if ($row['is_supermarchand'] == "0")
				{
					$merchant = array();

					$merchant['merchant_id'] = $row['id'];
					$merchant['merchant_name'] = $row['name'];
					$merchant['label_id'] = $row['label_id'];
					$merchant['logo'] = $row['logo'];
					$merchant['address'] = $row['address'] . ", " . $row['zip_code'] . ", " .$row['city'];
					$merchant['latitude'] = $row['latittude'];
					$merchant['longitude'] = $row['longitude'];
					$merchant['phone'] = $row['phone'];
					if(isset($row['distance'])) {
						$merchant['distance'] = $row['distance'];
					}

					array_push($merchant_array, $merchant);
				}
			}
			return TRUE;
		}

		return FALSE;
	}



	function getMerchants($jsonArray)
	{
		global $tbl_merchant, $tbl_mobileuser, $tbl_merchant_has_mobileuser, $merchant_array, $errorMsg, $array_sort;

		/// Check if all parameters are present
		if (!isset($jsonArray->usr_id) || !isset($jsonArray->nb_merchants) || !isset($jsonArray->is_having_points) || !isset($jsonArray->offset))
		{
			$paremeterString = "::usr_id="
				. isset($jsonArray->usr_id) . "::nb_merchants="
				. isset($jsonArray->nb_merchants) . "::is_having_points="
				. isset($jsonArray->is_having_points). "::offset="
				. isset($jsonArray->offset);

			$errorMsg = "Bad parameters... Some parameters who are mandatory were not found" . $paremeterString;
			return FALSE;
		}

		// Check if location parameters are present
		$have_location = FALSE;
		if (isset($jsonArray->latitude) && isset($jsonArray->longitude) && !empty($jsonArray->latitude) && !empty($jsonArray->longitude))
			$have_location = TRUE;

		if ($jsonArray->is_having_points == "0")
			$have_point = FALSE;
		else
			$have_point = TRUE;

		/// Get the User
		$sqlGetUser = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			.mysql_real_escape_string($jsonArray->usr_id)
			. "'";

		$result = mysql_query($sqlGetUser);

		if ($result == FALSE || !mysql_num_rows($result))
		{
			if ($result == FALSE)
				$errorMsg = "Error: Problem with the DB.";
			else if (!mysql_num_rows($result))
				$errorMsg = "Error: No user corresponding with this usr_id";
			return FALSE;
		}

		$user = mysql_fetch_array($result);

		/// Login to Loyalty server
		if (($youfid_access = doLoyaltyLogin()) == FALSE)
		{
			$errorMsg = "Error: Login to loyalty server failure";
			return FALSE;
		}

		/*$wsAccess = $youfid_access->wsAccess;
		$wsAccessPublicKey = $wsAccess->wsAccessPublicKey;
		$wsAccessToken =  $wsAccess->wsAccessToken;*/

		/// On cherche a obtenir une premiere liste de marchands non triée
		// $user, $latitude, $longitude, $offset, $nb_rows, $wsAccess

		if ($have_point == FALSE) {
			if($have_location)
				$res = doGetMerchantWithoutPoints($user, $jsonArray->search, $jsonArray->latitude, $jsonArray->longitude, $jsonArray->offset, $jsonArray->nb_merchants, $youfid_access->wsAccess);
			else
				$res = doGetMerchantWithoutPoints($user, $jsonArray->search, 0, 0, $jsonArray->offset, $jsonArray->nb_merchants, $youfid_access->wsAccess);
		} else {
			if($have_location)
				$res = doGetMerchantWithPoints($user, $jsonArray->search, $jsonArray->latitude, $jsonArray->longitude, $jsonArray->offset, $jsonArray->nb_merchants, $youfid_access->wsAccess);
			else
				$res = doGetMerchantWithPoints($user, $jsonArray->search, 0, 0, $jsonArray->offset, $jsonArray->nb_merchants, $youfid_access->wsAccess);
		}

		//$logger->log('debug', 'getMerchants_V2', " Before first sort...", Logger::GRAN_MONTH);

		/// Si il se passe une erreur lors de la derniere operation, return FALSE
		if ($res == FALSE)
			return FALSE;

		$res = $array_sort;

		if ($have_point == FALSE && $have_location == FALSE)
		{
			$errorMsg = "Error: You have to specify user location for a merchant request with 'having_point' = 0";
			return FALSE;
		}

		// On effectue le tri par distance croissante
		if (($have_point == FALSE && $have_location == TRUE)
			||	($have_point == TRUE && $have_location == TRUE))
		{
			// On ajoute enfin les marchands en fonction du range. Pour le moment les 10 premiers
			$sort_index = 0;
			for($index = 0; isset($res[$index]); $index += 1)
			{
				$merchant = array();

				$merchant['merchant_id'] = $res[$index]['id'];
				$merchant['merchant_name'] = $res[$index]['name'];
				$merchant['label_id'] = $res[$index]['label_id'];
				$merchant['logo'] = $res[$index]['logo'];
				//$merchant['address'] = $res[$index]['address'];
				$merchant['address'] = $res[$index]['address'] . ", " . $res[$index]['zip_code'] . ", " .$res[$index]['city'];
				$merchant['latitude'] = $res[$index]['latittude'];
				$merchant['longitude'] = $res[$index]['longitude'];
				$merchant['phone'] = $res[$index]['phone'];

				$merchant['last_use'] = $res[$index]['last_use'];
				$merchant['total_use'] = $res[$index]['nb_use'];

				$merchant['nb_pts'] = strval($res[$index]['nb_pts']);

				if( isset($res[$index]['distance']))
					$merchant['distance'] = $res[$index]['distance'];
				else
					$merchant['distance'] = "";

				//echo("1_MERCHANT==>id=" . $res[$index]['id'] . " last_use=" . $merchant['last_use'] . " total_use=" . $merchant['total_use']);

				$nb_use = intval($merchant['total_use']);
				if (($have_point == FALSE && $have_location == TRUE) || $nb_use > 0)
				{
					//$merchant['distance'] = $res[$index]['distance'];
					$merchant_array[$sort_index] = $merchant;
					$sort_index += 1;
				}

			}

			return TRUE;
		}

		/// Tri par ordre de points decroissant
		else if ($have_point == TRUE && $have_location == FALSE)
		{
			$sort_index = 0;
			for($index = 0; isset($res[$index]); $index++)
			{
				$merchant = array();

				$merchant['merchant_id'] = $res[$index]['id'];
				$merchant['merchant_name'] = $res[$index]['name'];
				$merchant['label_id'] = $res[$index]['label_id'];
				$merchant['logo'] = $res[$index]['logo'];
				//$merchant['address'] = $res[$index]['address'];
				$merchant['address'] = $res[$index]['address'] . ", " . $res[$index]['zip_code'] . ", " .$res[$index]['city'];
				$merchant['latitude'] = $res[$index]['latittude'];
				$merchant['longitude'] = $res[$index]['longitude'];
				$merchant['phone'] = $res[$index]['phone'];

				$merchant['last_use'] = $res[$index]['last_use'];
				$merchant['total_use'] = $res[$index]['nb_use'];

				$merchant['nb_pts'] = $res[$index]['nb_pts'];

				//echo("2_MERCHANT==>id=" . $res[$index]['id'] . " last_use=" . $merchant['last_use'] . " total_use=" . $merchant['total_use']);

				$nb_use = intval($merchant['total_use']);
				if ($nb_use > 0)
				{
					$merchant_array[$sort_index] = $merchant;
					$sort_index += 1;
				}
			}
			return TRUE;
		}

		$errorMsg = "Error: Unknown error";
		return FALSE;
	}
	/*
	function getWithPointMerchantList($user)
	{
		global $tbl_merchant_has_mobileuser, $tbl_merchant;

		$query = "SELECT * FROM $tbl_merchant_has_mobileuser WHERE `mobileuser_id`='"
			.	mysql_real_escape_string($user['id'])
			. "'";

		$result = mysql_query($query);

		if ($result == FALSE)
			return -1;

		$merchant_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$merchant_query = "SELECT * FROM $tbl_merchant WHERE `id`='"
				. mysql_real_escape_string($row['marchand_id'])
				. "'";

			$merchant_result = mysql_query($merchant_query);
			if ($merchant_row = mysql_fetch_array($merchant_result))
			{
				if (!in_array($merchant_row['id'], $merchant_array))
					array_push($merchant_array, $merchant_row['id']);
			}
		}
		$logger->log('debug', 'getMerchants', "Found merchants = " . print_r($merchant_array, true));
		return $merchant_array;
	}
	*/
	function getWithPointMerchantList($user)
	{
		$query = "SELECT m.id FROM `marchand` m INNER JOIN `marchand_has_mobileuser` mm ON m.`id` = mm.`marchand_id` WHERE mm.`mobileuser_id` = '" . mysql_real_escape_string($user['id']) . "'";
		$result = mysql_query($query);

		$merchant_array = array();
		while ($merchant_row = mysql_fetch_array($result))
		{
			array_push($merchant_array, $merchant_row['id']);
		}

		$logger->log('debug', 'getMerchants', "Found merchants = " . print_r($merchant_array, true));

		return $merchant_array;
	}

	/// Cas ou HAVE_POINT == 1
	function doGetMerchantWithPoints($user, $search, $latitude, $longitude, $offset, $nb_rows, $wsAccess)
	{
		global $tbl_merchant, $tbl_mobileuser, $tbl_merchant_has_mobileuser, $merchant_array, $errorMsg, $array_sort, $logger, $superMerchantId, $superFilterQry;

		// On recupere la liste des marchands sur lequel le client a des points
		$have_points_merchants = doMobiuserAppsRequest($user, $wsAccess);

		$merchant_points = array();
		foreach($have_points_merchants as $rw) {
			$merchant_points[$rw->application->publicId] = $rw->totalPoints;
		}

		//$logger->log('debug', 'getMerchants', "Merchants From loyalty = " . print_r($merchant_points, true), Logger::GRAN_MONTH);

		$uid = $user['id'];
		if($latitude != 0 && $longitude != 0)
			$sqlGetMerchants = "SELECT m.id, m.name, m.application_id, m.label_id, m.logo, m.address, m.zip_code, m.city, m.phone, m.latittude, m.longitude, m.is_active, m.supermarchand_id, m.is_supermarchand,
									   mm.nb_use, (SELECT max(transaction_date) FROM `transaction` WHERE `marchand_id` = m.id AND `mobileuser_id` = '$uid') AS last_use,
									   GeoDistDiff('km', m.latittude, m.longitude, $latitude, $longitude) AS distance
								FROM `marchand` m
								INNER JOIN `marchand_has_mobileuser` mm ON mm.marchand_id = m.id
								WHERE mm.mobileuser_id = '$uid' AND m.is_active = 1 AND m.is_supermarchand = 0 AND mm.nb_use > 0  " .
								($superFilterQry !== null ? "AND " . $superFilterQry : ""  ) .
								(!empty($search) ? " AND m.name like '%" . mysql_escape_string($search) . "%'" : "") .
								"ORDER BY distance ASC
								LIMIT $offset, $nb_rows";
		else
			$sqlGetMerchants = "SELECT m.id, m.name, m.application_id, m.label_id, m.logo, m.address, m.zip_code, m.city, m.phone, m.latittude, m.longitude, m.is_active, m.supermarchand_id, m.is_supermarchand,
									   mm.nb_use, (SELECT max(transaction_date) FROM `transaction` WHERE `marchand_id` = m.id AND `mobileuser_id` = '$uid') AS last_use
								FROM `marchand` m
								INNER JOIN `marchand_has_mobileuser` mm ON mm.marchand_id = m.id
								WHERE mm.mobileuser_id = '$uid' AND m.is_active = 1 AND m.is_supermarchand = 0 AND mm.nb_use > 0  " .
								($superFilterQry !== null ? "AND " . $superFilterQry : ""  ) .
								(!empty($search) ? " AND m.name like '%" . mysql_escape_string($search) . "%'" : "") .
								"LIMIT $offset, $nb_rows";

		//$logger->log('debug', 'getMerchants', "Merchant With Points RQ = " . $sqlGetMerchants, Logger::GRAN_MONTH);

		$result = mysql_query($sqlGetMerchants);

		if ($result == FALSE)
		{
			$errorMsg = "Error: Error withe DB during Merchant query";
			return FALSE;
		}

		$merchant_index = 0;
		while ($row = mysql_fetch_array($result))
		{
			if(isset($merchant_points[$row['application_id']]))
			{
				$row['nb_pts'] = $merchant_points[$row['application_id']];
			}
			else // Si aucun points n'a été ajouté, on set nb_points a 0
			{
				$row['nb_pts'] = 0;
			}

			//if ($row['is_active'] == "1" && $row['is_supermarchand'] == "0")
			//{
			$array_sort[$merchant_index] = $row;
			$merchant_index += 1;
			//}
		}

		//$logger->log('debug', 'getMerchants', "Merchant With Points RES = " . print_r($array_sort, true), Logger::GRAN_MONTH);

		return TRUE;
	}

	/// Cas ou HAVE_POINT == 0
	function doGetMerchantWithoutPoints($user, $search, $latitude, $longitude, $offset, $nb_rows, $wsAccess)
	{
		global $tbl_merchant, $tbl_mobileuser, $tbl_merchant_has_mobileuser, $merchant_array, $errorMsg, $array_sort, $superMerchantId, $logger, $superFilterQry;

		/// On recupere la liste des marchands sur lequel le client a des points
		$have_points_merchants = doMobiuserAppsRequest($user, $wsAccess);

		$merchant_points = array();
		foreach($have_points_merchants as $rw) {
			$merchant_points[$rw->application->publicId] = $rw->totalPoints;
		}

		$uid = $user['id'];

		if($latitude != 0 && $longitude != 0)
			$sqlGetMerchants = "SELECT m2.*, (SELECT max(transaction_date) FROM `transaction` WHERE `marchand_id` = m2.id AND `mobileuser_id` = '$uid') AS last_use,
			(SELECT SUM(nb_use) FROM `marchand_has_mobileuser` mm WHERE mm.marchand_id = m2.id AND mm.mobileuser_id = '$uid') AS nb_use
			FROM (
				SELECT m.id, m.name, m.application_id, m.label_id, m.logo, m.address, m.zip_code, m.city, m.phone, m.latittude, m.longitude, m.is_active, m.supermarchand_id, m.is_supermarchand,
					   GeoDistDiff('km', m.latittude, m.longitude, $latitude, $longitude) AS distance
				FROM `marchand` m
				WHERE m.is_active = 1 AND m.is_supermarchand = 0 " .
				($superFilterQry !== null ? "AND " . $superFilterQry : ""  ) .
				(!empty($search) ? " AND m.name like '%" . mysql_escape_string($search) . "%'" : "") .
				"ORDER BY distance ASC
				LIMIT $offset, $nb_rows
			) AS m2";
		else
			$sqlGetMerchants = "SELECT m2.*, (SELECT max(transaction_date) FROM `transaction` WHERE `marchand_id` = m2.id AND `mobileuser_id` = '$uid') AS last_use,
			(SELECT SUM(nb_use) FROM `marchand_has_mobileuser` mm WHERE mm.marchand_id = m2.id AND mm.mobileuser_id = '$uid') AS nb_use
			FROM (
				SELECT m.id, m.name, m.application_id, m.label_id, m.logo, m.address, m.zip_code, m.city, m.phone, m.latittude, m.longitude, m.is_active, m.supermarchand_id, m.is_supermarchand
				FROM `marchand` m
				WHERE m.is_active = 1 AND m.is_supermarchand = 0 " .
				($superFilterQry !== null ? "AND " . $superFilterQry : ""  ) .
				(!empty($search) ? " AND m.name like '%" . mysql_escape_string($search) . "%'" : "") .
				"LIMIT $offset, $nb_rows
			) AS m2";

		// $logger->log('debug', 'getMerchants', "Merchant Without Points RQ = " . $sqlGetMerchants, Logger::GRAN_MONTH);
		// error_log("SQL qry: " . $sqlGetMerchants, 3, '/var/log/apache2/youfid-error.log');

    $result = mysql_query($sqlGetMerchants);

		if ($result == FALSE)
		{
			$errorMsg = "Error: Error withe DB during Merchant query";
			return FALSE;
		}

		$merchant_index = 0;
		while ($row = mysql_fetch_array($result))
		{
			if(isset($merchant_points[$row['application_id']]))
			{
				$row['nb_pts'] = $merchant_points[$row['application_id']];
			}
			else // Si aucun points n'a été ajouté, on set nb_points a 0
			{
				$row['nb_pts'] = 0;
			}

			//if ($row['is_active'] == "1" && $row['is_supermarchand'] == "0")
			//{
			$array_sort[$merchant_index] = $row;
			$merchant_index += 1;
			//}
		}

		return TRUE;
	}

	/// Recupere sur loyalty la liste des application marchands ou le client a des points. Retourne FALSE en cas d'erreur
	function doMobiuserAppsRequest($user, $wsAccess)
	{
		global $url_loyalty;
		// Urls
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
			//echo "<br/>>>> get_application success <br/> response=" . $res_get_application;
			return $res_get_application->mobileUserApplications;
		}
		/*if(!empty($res_get_application) && !empty($res_get_application->error) && $res_get_application->error->code == 404)
		{
			return array();
		}*/

		// If get_application failed
		/*else echo "<br/> get_application failure <br/> response=" . $res_get_application_json;*/

		return (array());
	}

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


