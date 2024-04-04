<?php

	require_once("Logger.class.php");

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

		$query = "UPDATE $tbl_promo_has_mobile_user SET `has_been_read`='1' WHERE `message_id`='"
			. mysql_real_escape_string($msg_id)
			. "' && `mobileuser_id`='"
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
	if (isset($jsonArray->usr_id) && isset($jsonArray->last_id))
	{
		$usr_id = $jsonArray->usr_id;
		$last_id = $jsonArray->last_id;

		if($last_id == "") $last_id = "0";

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

			//echo $sqlGetPromo;
		$result = mysql_query($sqlGetPromo);


		/// Nouveaux messages
		if ($result != FALSE)
		{
			$index = 0;
			while ($row = mysql_fetch_array($result))
			{
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

					$sqlMarchand = "SELECT * FROM $tbl_marchand WHERE id = '"
					. mysql_real_escape_string($row['marchand_id'])
					. "';";

					$marchandResult = mysql_query($sqlMarchand);
					$logger->log('debug', 'getMessages', "query=" . $sqlMarchand, Logger::GRAN_MONTH);
					if ($row['marchand_id'] == 0)
					{
						$promoArray['merchant_logo'] = "http://backoffice.youfid.fr/static/logos/logoyoufid_hd.png";
						$promoArray['merchant_name'] = "YouFid";

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if (mysql_num_rows($marchandResult))
					{
						$marchandRow = mysql_fetch_array($marchandResult);
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
						$promoArray['merchant_logo'] = "http://backoffice.youfid.fr/static/logos/logoyoufid_hd.png";
						$promoArray['merchant_name'] = "YouFid";

						$messagesArray[$index] = $promoArray;
						$index += 1;
					}
					else if (mysql_num_rows($marchandResult))
					{
						$marchandRow = mysql_fetch_array($marchandResult);
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
					break;
				}
				else
					set_msg_read($row['message_id'], $usr_id);
			}
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
	echo(json_encode(array_map_utf8_encode($jsonResult)));

	$logger->log('debug', 'getMessages', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);


