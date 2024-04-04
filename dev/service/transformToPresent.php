<?php

	require_once('Logger.class.php');
	require_once('dbLogInfo.php');
	require_once('utils.php');

	if (!isset($logger))
		$logger = new Logger('logs/');

	function doLog($message)
	{
		global $logger;

		if (isset($logger))
			$logger->log('debug', 'transformToPresent', $message, Logger::GRAN_MONTH);
	}

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
      doLog("Merchant with id " . $jsonArray->merchant_id . " not found");
    }
  }
	
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$tbl_merchant="marchand";
	$tbl_mobileuser="mobileuser";
	$tbl_transaction="transaction";

	$tbl_msg = "message";
	$tbl_msg_hmbu = "message_has_mobileuser";

	// $json = utf8_encode($json);
	// $jsonArray = json_decode($json);

	doLog("Request=" . $json);

	/// Values for return
	$total_pts = "0";
	$errorMsg = "";
	$res = transformToPresent($jsonArray);

	if (!$res) {
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	} else {
		$jsonResult['status'] = "ok";
		$jsonResult['total_pts'] = $total_pts;
	}

	/// Envoi de la reponse
	doLog("Response=" . json_encode(array_map_utf8_encode($jsonResult)));
	echo(json_encode(array_map_utf8_encode($jsonResult)));

	register_recu($jsonArray);

	function do_recu_row($product, $usr_id, $merchant_id)
	{
		global $tbl_msg, $tbl_msg_hmbu;

		$query = "INSERT INTO $tbl_msg SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `type`='recu', `points`='"
			. mysql_real_escape_string($product->cost)
			. "', `message`='"
			. mysql_real_escape_string($product->name)
			. "', `start_date`=Now(), `finish_date`=Now(), `is_validated`='1'";

		doLog("inDo_recu_row::query1=" . $query);

		$result = mysql_query($query);

		if ($result == FALSE)
			return FALSE;

		$message_id = mysql_insert_id();

		$query = "INSERT INTO $tbl_msg_hmbu SET `mobileuser_id`='"
			. mysql_real_escape_string($usr_id)
			. "', `has_been_read`='0', `date_creation`=Now(), `message_id`='"
			. mysql_real_escape_string($message_id)
			. "'";

		doLog("inDo_recu_row::query2=" . $query);
		$result = mysql_query($query);

		if ($result == FALSE)
			return FALSE;
		return TRUE;
	}

	function register_recu($jsonArray)
	{
		global $tbl_merchant;

		if (!isset($jsonArray->usr_id) || !isset($jsonArray->merchant_id) || !isset($jsonArray->products))
		{
			//doLog("MissingParameters::usr_id=" . $jsonArray->usr_id . " merchant_id=" . $jsonArray->merchant_id . " products=" . $jsonArray->products);
			doLog("inRegiter_recu::problem with jsonArray");
			return FALSE;
		}

		$query = "SELECT * FROM $tbl_merchant WHERE `id`='"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";

		doLog("inRegiter_recu::query=" . $query);
		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;

		if ($row = mysql_fetch_array($result))
		{
			for($index = 0; isset($jsonArray->products[$index]); $index++)
			{
				if ($row['is_accueil_client'] == "0")
					do_recu_row($jsonArray->products[$index], $jsonArray->usr_id, $jsonArray->merchant_id);
			}
		}
		return TRUE;
	}

	$transaction = FALSE;
	function transformToPresent($jsonArray)
	{
		global $errorMsg, $tbl_merchant, $tbl_mobileuser, $total_pts, $transaction;

		if (!isset($jsonArray->usr_id) || !isset($jsonArray->merchant_id) || !isset($jsonArray->products))
		{
			//doLog("MissingParameters::usr_id=" . $jsonArray->usr_id . " merchant_id=" . $jsonArray->merchant_id . " products=" . $jsonArray->products);
			$errorMsg = "Error: Some parameters who are mandatory are missing...";
			return FALSE;
		}

		/// Recuperation du User associé a l'id
		$sqlGetUser = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			. mysql_real_escape_string($jsonArray->usr_id)
			. "'";

		$userResult = mysql_query($sqlGetUser);

		/// Recuperation du Marchand associé a l'id
		$sqlGetMerchant = "SELECT * FROM $tbl_merchant WHERE `id`='"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";

		$merchantResult = mysql_query($sqlGetMerchant);

		if (!$userResult || !$merchantResult || !($user = mysql_fetch_array($userResult, MYSQL_ASSOC)) || !($merchant = mysql_fetch_array($merchantResult, MYSQL_ASSOC)))
		{
			$errorMsg = "Error: Problem with the DB during getting user and merchant.";
			return FALSE;
		}

		/// Do login
		if (($loyalty_access = doLoyaltyLogin()) == FALSE)
		{
			$errorMsg = "Error: Login to loyalty server failure";
			return FALSE;
		}

		$wsAccess = $loyalty_access->wsAccess;

		/// On compte les points a soustraire
		$nb_points = 0;
		$nb_cadeaux = 0;
		for($index = 0; isset($jsonArray->products[$index]); $index++)
		{
			$nb_points += $jsonArray->products[$index]->cost;
			$nb_cadeaux += 1;
		}

		if(!empty($merchant['loyalty_program_id'])) {
			$lp_query = mysql_query("SELECT * FROM loyalty_program WHERE id = " . $merchant['loyalty_program_id']);
			if(mysql_num_rows($lp_query)) {
				$program = mysql_fetch_array($lp_query);
				doLog("loyalty program " . print_r($program, true));
				if(strtoupper($program['program_type']) === 'DOMINOS') {
					registerLastGift($user['id'], $program['id'], $jsonArray->products);
				}
			}

			$total_pts = strval(get_user_points($user['public_id'], $merchant['application_id']));

			return TRUE;
		} else {
			/// Requete sur Loyalty
			$result = doWithdrawPoints($user, $merchant, $wsAccess, $nb_points);

			if ($result == FALSE)
			{
				$errorMsg = "Error: Problem during withdrawPoints on LOYALTY";
				return FALSE;
			}

			/// Ajout de la transaction en bdd
			addTransaction($nb_cadeaux, $nb_points, $jsonArray->usr_id, $jsonArray->merchant_id, $transaction);

			$total_pts = strval($result->totalPoints);

			return TRUE;
		}
	}

	function registerLastGift($mobileuser_id, $program_id, $products) {

		if(empty($products)) {
			return false;
		}

		// On devrait trier les produits par ordre decroissant des couts et prendre le premier.
		// En pratique, il n'existe qu'un seul produit dans $products.
		$giftId = $products[0]->id;
		$user_program_qry = mysql_query(
			"SELECT * FROM mobileuser_loyalty_program
			WHERE mobileuser_id = $mobileuser_id
			AND loyalty_program_id = $program_id"
		);
		
		if(mysql_num_rows($user_program_qry)) {
			$update_qry = "UPDATE mobileuser_loyalty_program SET last_received_gift = $giftId, gitft_received_at = NOW() WHERE mobileuser_id = $mobileuser_id AND loyalty_program_id = $program_id";
			// doLog("User program update qry: " . $update_qry);
			mysql_query($update_qry);
		} else {
			mysql_query(
				"INSERT INTO mobileuser_loyalty_program
				VALUES ($mobileuser_id, $program_id, NOW(), $giftId, NOW())"
			);
		}
	}
	
	function addTransaction($nb_cadeaux, $nb_points, $mobileusr_id, $merchand_id, $transaction)
	{
		global $logger, $tbl_transaction;

		$today = date("Y-m-d");

		$query = "INSERT INTO $tbl_transaction SET `mobileuser_id`='"
			. mysql_real_escape_string($mobileusr_id)
			. "', `marchand_id`='"
			. mysql_real_escape_string($merchand_id)
			. "', `id_loyalty`='"
			. mysql_real_escape_string($transaction->publicId)
			. "' , `value`='"
			. mysql_real_escape_string('-' . $nb_points)
			. "', `nb_cadeaux`='"
			. mysql_real_escape_string($nb_cadeaux)
			. "', `transaction_date`=NOW()";

		doLog("AddTransaction::query=" . $query);

		$result = mysql_query($query);

		return $result;
	}

	/// Soustrait $nb_point au user correspondant sur le server de LOYALTY
	function doWithdrawPoints($user, $merchant, $wsAccess, $nb_points)
	{
		global $transaction, $url_loyalty;

		/// Urls
		$service_base_url = $url_loyalty . "services/";
		$withdraw_points_service = "transaction/withdrawpoints";

		$req_withdraw_points = array(
			"wsAccessPublicKey" => $wsAccess->wsAccessPublicKey,
			"wsAccessToken" => $wsAccess->wsAccessToken,
			"mobileUserPublicId" => $user['public_id'],
			"applicationPublicId" => $merchant['application_id'],
			"points" => $nb_points
		);

		//echo("REQUEST::" . json_encode($req_withdraw_points));

		$req_withdraw_points_json = json_encode($req_withdraw_points);
		$res_withdraw_points_json = postRequest($service_base_url . $withdraw_points_service, $req_withdraw_points_json);
		$res_withdraw_points = json_decode($res_withdraw_points_json);

		//echo("RESPONSE::" . $res_withdraw_points_json);

		doLog("inDoWithdrawPoints::response=" . $res_withdraw_points_json);

		/// Success
		if(!empty($res_withdraw_points) && !empty($res_withdraw_points->error) && $res_withdraw_points->error->code == 0)
		{
			$transaction = $res_withdraw_points->transaction;
			return $res_withdraw_points->mobileUserApplication;
		}

		return FALSE;
	}

	function doLoyaltyLogin()
	{
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

