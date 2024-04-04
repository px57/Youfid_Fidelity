<?php

	require_once '_security.php';

	require_once("Logger.class.php");
	require_once 'utils.php';

	yf_security_log_event('messages');

	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");

	/// table name
	$tbl_user="mobileuser";
	$tbl_marchand="marchand";
	$tbl_promo="message";
	$tbl_promo_has_mobile_user="message_has_mobileuser";
	require_once('dbLogInfo.php');

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	$logger->log('debug', 'getMessages', "Request=" . $json, Logger::GRAN_MONTH);

	$error = false;
	$errorMsg = "";

	$messagesArray = array();

	/// Set have_been_read = 1
	function set_msg_read($msg_id, $usr_id)
	{
		global $tbl_promo_has_mobile_user, $logger;

		if(!is_array($msg_id))
		{
			$msg_id = array($msg_id);
		}

		$ids_string = implode(",", $msg_id);

		$query = "UPDATE `message_has_mobileuser` SET `has_been_read` = '1' WHERE `message_id` IN ("
			. mysql_real_escape_string($ids_string)
			. ") AND `mobileuser_id`='"
			. mysql_real_escape_string($usr_id)
			. "'";

		if (isset($logger))
			$logger->log('debug', 'getMessages', "inSet_msg_read::query=" . $query, Logger::GRAN_MONTH);

		$result = mysql_query($query);
		if ($result == FALSE)
		{
			if (isset($logger))
				$logger->log('debug', 'getMessages', "FAIL TO SET have_been_read::message_id=" . $msg_id, Logger::GRAN_MONTH);
			return FALSE;
		}
		return TRUE;
	}

	//if (isset($jsonArray->usr_id))
	 $logger->log('debug', 'getMessages', "=====TST User id = " . $jsonArray->usr_id . ", last id = " . $jsonArray->last_id, Logger::GRAN_MONTH);
	if (isset($jsonArray->usr_id) && isset($jsonArray->last_id))
	{
		$usr_id = $jsonArray->usr_id;
		$last_id = $jsonArray->last_id;

		if($last_id == "") $last_id = "0";

		if($last_id != "0")
			$sqlGetPromo = "Select * from message_has_mobileuser mbu JOIN message m WHERE mbu.mobileuser_id="
				.mysql_real_escape_string($usr_id)
				." AND mbu.has_been_read = 0"
				." AND m.id = mbu.message_id"
				." AND ((m.type='promo'"
				." AND TO_DAYS(`start_date`) <= TO_DAYS(NOW())"
				." AND TO_DAYS(`finish_date`) >= TO_DAYS(NOW())"
				." AND `is_validated` = 1)"
				." OR(m.type='recu'"
				." AND TO_DAYS(`start_date`) <= TO_DAYS(NOW())"
				." AND TO_DAYS(`start_date`) +15 >= TO_DAYS(NOW())))";

		else
			$sqlGetPromo = "Select * from message_has_mobileuser mbu JOIN message m WHERE mbu.mobileuser_id="
			.mysql_real_escape_string($usr_id)
			." AND mbu.message_id >"
			.mysql_real_escape_string($last_id)
			." AND m.id = mbu.message_id"
			." AND ((m.type='promo'"
			." AND TO_DAYS(`start_date`) <= TO_DAYS(NOW())"
			." AND TO_DAYS(`finish_date`) >= TO_DAYS(NOW())"
			." AND `is_validated` = 1)"
			." OR(m.type='recu'"
			." AND TO_DAYS(`start_date`) <= TO_DAYS(NOW())"
			." AND TO_DAYS(`start_date`) +15 >= TO_DAYS(NOW())))";

			//echo $sqlGetPromo;
		$logger->log('debug', 'getMessages', '======== Begin SQL QRY Get Promos =======', Logger::GRAN_MONTH);
		$logger->log('debug', 'getMessages', 'MSQRY: ' . $sqlGetPromo, Logger::GRAN_MONTH);
		$result = mysql_query($sqlGetPromo);
		$logger->log('debug', 'getMessages', '======== End SQL QRY Get Promos =======', Logger::GRAN_MONTH);

		$msg_ids = array();
		/// Nouveaux messages
		if ($result != FALSE)
		{
			$logger->log('debug', 'getMessages', '======== Begin Update read promos =======', Logger::GRAN_MONTH);
			$index = 0;
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				$count++;
				$promoArray = array();

				if ($row['type'] == "promo")
				{
					$promoArray['id'] = $row['id'];
					$promoArray['type'] = $row['type'];
					$promoArray['message'] = $row['message'];
					$promoArray['pts'] = $row['points'];
					$promoArray['date'] = $row['finish_date'];
					$promoArray['merchant_id'] = $row['marchand_id'];
					$promoArray['detail'] = $row['detail'];

					/// Recuperation des infos relatives a un marchand

					$sqlMarchand = "SELECT * FROM $tbl_marchand WHERE id = '" . mysql_real_escape_string($row['marchand_id']) . "';";

					$marchandResult = mysql_query($sqlMarchand);
					$logger->log('debug', 'getMessages', "query=" . $sqlMarchand, Logger::GRAN_MONTH);
					if ($row['marchand_id'] == 0)
					{
						$promoArray['merchant_id']   = null;
						$promoArray['supermarchand_id'] = null;
						$promoArray['merchant_logo'] = "http://backoffice.youfid.fr/static/logos/logoyoufid_hd.png";
						$promoArray['merchant_name'] = "YouFid";

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if (mysql_num_rows($marchandResult))
					{
						$marchandRow = mysql_fetch_array($marchandResult);
						$promoArray['merchant_id']   = $marchandRow['id'];
						$promoArray['supermarchand_id'] = $marchandRow['supermarchand_id'];
						$promoArray['merchant_logo'] = $marchandRow['logo'];
						$promoArray['merchant_name'] = $marchandRow['name'];

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if ($marchandResult == FALSE)
					{
						$error = TRUE;
						$logger->log('debug', 'getMessages', "ERROR_1", Logger::GRAN_MONTH);
					}
				}

				if ($row['type'] == "recu")
				{
					$promoArray['id'] = $row['id'];
					$promoArray['type'] = $row['type'];
					$promoArray['message'] = $row['message'];
					$promoArray['pts'] = $row['points'];
					$promoArray['detail'] = $row['detail'];
					$promoArray['date'] = $row['start_date'];
					$promoArray['merchant_id'] = $row['marchand_id'];

					/// Recuperation des infos relatives a un marchand
					$sqlMarchand = "SELECT * FROM $tbl_marchand WHERE id = '"
					. mysql_real_escape_string($row['marchand_id'])
					. "';";

					$logger->log('debug', 'getMessages', "query=" . $sqlMarchand, Logger::GRAN_MONTH);
					$marchandResult = mysql_query($sqlMarchand);

					if ($row['marchand_id'] == 0)
					{
						$promoArray['merchant_id']   = null;
						$promoArray['supermarchand_id'] = null;
						$promoArray['merchant_logo'] = "http://backoffice.youfid.fr/static/logos/logoyoufid_hd.png";
						$promoArray['merchant_name'] = "YouFid";

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if (mysql_num_rows($marchandResult))
					{
						$marchandRow = mysql_fetch_array($marchandResult);
						$promoArray['merchant_id']   = $marchandRow['id'];
						$promoArray['supermarchand_id'] = $marchandRow['supermarchand_id'];
						$promoArray['merchant_logo'] = $marchandRow['logo'];
						$promoArray['merchant_name'] = $marchandRow['name'];

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if ($marchandResult == FALSE)
					{
						$error = TRUE;
						$logger->log('debug', 'getMessages', "ERROR_2", Logger::GRAN_MONTH);
					}
				}

				if ($error == TRUE)
				{
					$errorMsg = "Problem With the database";
					$msg_ids = null;
					break;
				}
				else{
					$msg_ids[] = $row['message_id'];
					//set_msg_read($row['message_id'], $usr_id);
				}
			}

			if(!empty($msg_ids) && count($msg_ids) > 0)
			{
				set_msg_read($msg_ids, $usr_id);
			}

			//$logger->log('debug', 'getMessages', '======== Updated promos : ' + $count, Logger::GRAN_MONTH);
			$logger->log('debug', 'getMessages', '======== Begin Update read promos =======', Logger::GRAN_MONTH);
		}
		/// else, pas de nouveaux messages
	}
	else {
		$error = true;
		$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	}

	if ($error == true)
	{
		$status = "error";
		$messagesArray = $errorMsg;
	}
	else
		$status = "ok";

	//$logger->log('debug', 'getMessages', "Request=" . $json, Logger::GRAN_MONTH);
	$jsonResult['status'] = $status;
	$jsonResult['messages'] = $messagesArray;

	# on filtre par supermarchand si necessaire
	if(@$jsonArray->supermarchand_id and is_array($jsonResult['messages']))
		foreach ($jsonResult['messages'] as $key => $message)
			if($message['supermarchand_id'] != $jsonArray->supermarchand_id)
				unset($jsonResult['messages'][$key]);

	echo(json_encode( $jsonResult));

	$logger->log('debug', 'getMessages', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);


