<?php

	require_once('Logger.class.php');
	require_once("utils.php");

	if (!isset($logger))
		$logger = new Logger('logs/');

	function doLog($message)
	{
		global $logger;

		if (isset($logger))
			$logger->log('debug', 'sendLocation', $message, Logger::GRAN_MONTH);
	}

	function doPrint($message)
	{
		//echo $message;
		/*global $logger;

		if (isset($logger))
			$logger->log('debug', 'sendLocation', $message, Logger::GRAN_MONTH);*/
	}

	/// table name
	$tbl_name="mobileuser";
	require_once('dbLogInfo.php');

	////////////////////////////////////////
	// Error properties
	$error = false;
	$errorMsg = "";

	////////////////////////////////////////
	// DataBase connection
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$merchant_has_mbuser = "marchand_has_mobileuser";

	function check_sm_subscription($super_merchant_id, $user_id)
	{
		global $merchant_has_mbuser;

		$query = "SELECT * FROM $merchant_has_mbuser WHERE `marchand_id`='"
			. mysql_real_escape_string($super_merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "'";

		$result = mysql_query($query);

		if (mysql_num_rows($result))
			return TRUE;

		return FALSE;
	}

	/// Inscrit un user a un super_marchand si il ne l'est pas.
	function subscribe_user_to_sm($merchant_id, $user_id)
	{
		global $merchant_has_mbuser;

		$merchant = get_merchant_2($merchant_id);
		if (!$merchant || $merchant['is_supermarchand'] == '1')
			return 2;
		if ($merchant['supermarchand_id'] == "-1")
			return 3;

		/// Verification de l'inscription
		if (check_sm_subscription($merchant['supermarchand_id'], $user_id))
			return 4;

		$query = "INSERT INTO $merchant_has_mbuser SET `marchand_id`='"
			. mysql_real_escape_string($merchant['supermarchand_id'])
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "', `nb_use`='1'";

		$result = mysql_query($query);
		return 0;
	}

	function get_mobileuser($user_id)
	{
		global $tbl_name;
		$query = "SELECT * FROM $tbl_name WHERE `id`='"
			. mysql_real_escape_string($user_id)
			. "'";

		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result))
			return $row;
		return FALSE;
	}

	// On vérifie si le user en question a deja recu une promo de la part du marchand
	function check_has_promo($merchant_id, $user_id)
	{
		//////////// WORKING REQUEST ////////////
		// SELECT * FROM message m JOIN message_has_mobileuser mhm
		// WHERE m.marchand_id = 64
		// AND mhm.message_id = m.id
		// AND mhm.mobileuser_id = 232

		$query = "SELECT * FROM message m JOIN message_has_mobileuser mhm"
			. " WHERE m.marchand_id = "
			. mysql_real_escape_string($merchant_id)
			. " AND mhm.message_id = m.id"
			. " AND mhm.mobileuser_id = "
			. mysql_real_escape_string($user_id)
			. " AND m.type='promo'"
			. " AND TO_DAYS( m.start_date ) <= TO_DAYS( NOW() )"
			. " AND TO_DAYS( m.finish_date ) >= TO_DAYS( NOW() )"
			. " AND m.is_validated = 1";

		doPrint("checkHasPromo = " . $query);

		$had_mbu_result = mysql_query($query);

		$promo = array();
		$result = FALSE;
		while ($row = mysql_fetch_array($had_mbu_result))
		{
			$promo['titre'] = $row['message'];
			$promo['message'] = $row['detail'];
			$result = $promo;
		}

		return $result;
	}

	// Fonction permettant d'indiquer que l'utilisateur a reçu un push géolocalisé de la part du marchand
	function update_last_notif($merchant_id, $mbu_id)
	{
		global $tbl_marchand_had_mbu;

		$tbl_histo = "histo_push";

		$query = "UPDATE marchand_has_mobileuser SET `last_notif`=current_timestamp() WHERE `mobileuser_id`='"
			. mysql_real_escape_string($mbu_id)
			. "' AND `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";

		$result = mysql_query($query);

		if ($result == FALSE)
			return FALSE;

		/// Update histo-Push

		$query = "INSERT INTO histo_push SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($mbu_id)
			. "', `push_date`=current_timestamp()";

		$result = mysql_query($query);

		if ($result == FALSE)
			return FALSE;

		return TRUE;
	}

	// Envoi du push géolocalisé par webservice
	function create_user_message($merchant_id, $titre, $message)
	{
		doPrint("</br>inCreateUserMessage");

		$time = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+ 15, date("Y"));
		$date_end = date("Y-m-d", $time);

		$query = "INSERT INTO message SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `type`='promo', `points`='0', `message`='"
			. mysql_real_escape_string($titre)
			. "', `detail`='"
			. mysql_real_escape_string($message)
			. "', `start_date`=Now(), `finish_date`='$date_end', `is_validated`='1'";

		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;

		doPrint("::insertId= " . mysql_insert_id());

		return mysql_insert_id();
	}

	// Récupération de l'id du jour
	function get_jour_id($jour)
	{
		$query = "SELECT * FROM jour WHERE `nom`='"
			. mysql_real_escape_string($jour)
			. "'";

		$res = -1;

		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result))
			$res = $row['id'];
		return $res;
	}

	// Passage du temps en minutes
	function time_to_minute($time)
	{
		$hours = substr($time, 0, 2);
		$hours = intval($hours);

		$minutes = substr($time, 3, 2);
		$minutes = intval($minutes);

		$current_time = ($hours * 60) + $minutes;
		return $current_time;
	}

	////////////////////////////////////////

	//       Getting Json Content         //

	////////////////////////////////////////

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	doPrint("QUERY::" . $json);

	/// sendLocation
	if (isset($jsonArray->usr_id) && isset($jsonArray->longitude) && isset($jsonArray->latitude) && isset($jsonArray->locationtime))
	{
		$lattitude = $jsonArray->latitude;
		$longitude = $jsonArray->longitude;
		$locationtime = $jsonArray->locationtime;

		$tab_jours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
		$current_day = $tab_jours[date('w', mktime(0,0,0,date('m'),date('d'),date('Y')))];
		$id_jour = get_jour_id($current_day);

		$current_time = date("H:i:s");
		$current_time = time_to_minute($current_time);

		$mobile_user = get_mobileuser($jsonArray->usr_id);

		//On actualise la localisation
		$sqlSetLocation = "UPDATE $tbl_name SET `lattitude`='"
			. mysql_real_escape_string($lattitude)
			. "', `longitude`='"
			. mysql_real_escape_string($longitude)
			. "', `locationtime`='"
			. mysql_real_escape_string($locationtime)
			. "' WHERE `id`='"
			. mysql_real_escape_string($jsonArray->usr_id)
			. "'";

		$sqlSetLocationResult = mysql_query($sqlSetLocation);

		if ($sqlSetLocationResult == false)
		{
			$error = true;
			$errorMsg = "Error with DB";
		}

		// PUSH GEOLOCALISE

		// On vérifie si le user est dans la zone d'un marchand:
		$queryGetProxyMerchants = "SELECT * FROM marchand WHERE is_active > 0 AND is_push_actif > 0 AND ("
			. "6371*acos(sin(latittude*pi()/180)*sin(("
			. floatval($lattitude)
			. ")*pi()/180) + cos(latittude*pi()/180)*cos(("
			. floatval($lattitude)
			. ")*pi()/180)*cos(("
			. floatval($longitude)
			. "-longitude)*pi()/180))"
			. ") * 1000 <= distance_push";

		doPrint("Check Geoloc::" . $queryGetProxyMerchants);

		$resultGetProxyMerchants = mysql_query($queryGetProxyMerchants);

		// Si il y'a des marchands répondant aux circonstances:
		while ($marchand = mysql_fetch_array($resultGetProxyMerchants))
		{
			doLog("</br>In marchand " . $marchand['name']);

			$queryA = "SELECT * from marchand_has_mobileuser WHERE marchand_id = "
			. $marchand['id']
			. " AND mobileuser_id = "
			. $mobile_user['id'];

			$result = mysql_query($queryA);
			$num = mysql_num_rows($result);

			// On fait le lien entre le user et le marchand si il n'existe pas.
			if($num==0)
			{
				$query = "INSERT INTO marchand_has_mobileuser SET `marchand_id`='"
					. $marchand['id']
					. "', `mobileuser_id`='"
					. $mobile_user['id']
					. "', `nb_use`='0'";

				//echo("</br>query = ".$query);
				doPrint("Add mobileuser to marchand::" . $query);

				$result = mysql_query($query);

				if ($result != FALSE && $marchand['supermarchand_id'] != -1)
				{
					subscribe_user_to_sm($marchand['id'], $mobile_user['id']);
				}
			}

			while ($row = mysql_fetch_array($result))
			{
				$marchand_has_mobileuser = $row;
			}

////////////////
			doLog("</br>User " . $mobile_user['prenom']." ".$mobile_user['nom']);

			// ON AFFINE
			$pushdata = array();
			$toSend = 0;

			// Récupération de la date du dernier push géolocalisé de ce marchand pour ce user
			$lastnotif = strtotime($marchand_has_mobileuser['last_notif']);
			doPrint("(lastNotif = " . $marchand_has_mobileuser['last_notif'] . " = " . $lastnotif . ")");

			// Si le user a déjà reçu un push géolocalisé
			if ($lastnotif > 0)
			{
////////////////////////
				doLog(" a deja recu un push de sa part");

				// Calcul du temps écoulé depuis le dit push géolocalisé
				$current_time_long = time("Y-m-d H:i:s");
				$last_notif_time = $lastnotif + intval($marchand['delay_push']) * 60;

				// Si le user n'a pas reçu de push de la part du marchand au cours des 15 derniers jours
				if ($current_time_long > $last_notif_time)
				{
////////////////////////////
					doLog(" il y'a plus de 15 jours");

					// 1.a) Si le user a déjà reçu une promo ciblée de la part du marchand:
					if($old_promo = check_has_promo($marchand['id'], $mobile_user['id']))
					{
////////////////////////////////
						doLog("</br>>>>Le user a deja recu une promo ciblee de la part du marchand: "
						. $old_promo['titre'] . " - " . $old_promo['message']);

						$toSend = 1;

						if ($id_msg = create_user_message($marchand['id'], $old_promo['titre'], $old_promo['message']))
						{
							// On assigne le message au user
							$queryAdd = "INSERT INTO message_has_mobileuser SET `mobileuser_id`='"
								. mysql_real_escape_string($mobile_user['id'])
								. "', `date_creation`=Now(), `message_id`='"
								. mysql_real_escape_string($id_msg)
								. "'";

							doPrint("</br>queryAdd = " . $queryAdd);

							$resultAdd = mysql_query($queryAdd);

							doPrint(" :: before pushdata :: ");

							$pushdata['ids'] = array();
							array_push($pushdata['ids'], "38");
							array_push($pushdata['ids'], "39");
							$pushdata['idsclient'] = array();
							array_push($pushdata['idsclient'], strval($mobile_user['id']));
							$pushdata['message'] = $old_promo['titre'] . " - " . $old_promo['message'];
							$pushdata['OS'] = "iOS_dev";

							doPrint(" :: after pushdata :: ");

							update_last_notif($marchand['id'], $mobile_user['id']);

							doPrint(" :: updated last notif :: ");
						}

						else $toSend = 0;
					}

					// 1.b) Si le user n'a jamais reçu de promo ciblée de la part du marchand:
					else
					{
////////////////////////////////
						doLog("</br>>>>Le user n'a jamais recu de promo ciblee de la part du marchand");

						// CREATE MSG

						$push_query = "SELECT * FROM pushgeoloc p JOIN message m"
						. " WHERE p.jour_id = "
						. mysql_real_escape_string($id_jour)
						. " AND p.date_debut <= "
						. mysql_real_escape_string($current_time)
						. " AND p.date_fin >= "
						. mysql_real_escape_string($current_time)
						. " AND p.marchand_id = "
						. mysql_real_escape_string($marchand['id'])
						. " AND m.id = p.msg_last_id AND m.is_validated = 1";

						doPrint("</br>Push query = " . $push_query);

						$push_result = mysql_query($push_query);
						while ($row = mysql_fetch_array($push_result))
						{
							$message = $row;
				////////////
							doPrint("</br></br>New message = " . $message['message'] . " - " . $message['detail']);

							$toSend = 1;
						}

						if ($toSend == 1 && $id_msg = create_user_message($marchand['id'], $message['message'], $message['detail']))
						{
							// On assigne le nouveau message au user
							$query = "INSERT INTO message_has_mobileuser SET `mobileuser_id`='"
							. mysql_real_escape_string($mobile_user['id'])
							. "', `date_creation`=Now(), `message_id`='"
							. mysql_real_escape_string($id_msg)
							. "'";

							doPrint("</br>assignMessageToUser:: " . $query);

							$result = mysql_query($query);

							$pushdata['ids'] = array();
							array_push($pushdata['ids'], "38");
							array_push($pushdata['ids'], "39");
							$pushdata['idsclient'] = array();
							array_push($pushdata['idsclient'], strval($mobile_user['id']));
							$pushdata['message'] = $message['message'] . " - " . $message['detail'];
							$pushdata['OS'] = "iOS_dev";

							update_last_notif($marchand['id'], $mobile_user['id']);
						}

						else $toSend = 0;
					}
				}
////////////////////////
				else doLog(" il y'a moins de 15 jours");
			}

			// Si le user n'a jamais reçu de push géolocalisé de la part de ce marchand
			else
			{
////////////////////////
				doLog(" n'a jamais recu de push geolocalise de sa part");

				// CREATE MSG

				$push_query = "SELECT * FROM pushgeoloc p JOIN message m"
						. " WHERE p.jour_id = "
						. mysql_real_escape_string($id_jour)
						. " AND p.date_debut <= "
						. mysql_real_escape_string($current_time)
						. " AND p.date_fin >= "
						. mysql_real_escape_string($current_time)
						. " AND p.marchand_id = "
						. mysql_real_escape_string($marchand['id'])
						. " AND m.id = p.msg_last_id AND m.is_validated = 1";

				doPrint("</br>Push query = " . $push_query);

				$push_result = mysql_query($push_query);
				while ($row = mysql_fetch_array($push_result))
				{
					$message = $row;
		////////////
					doPrint("</br></br>New message = " . $message['message'] . " - " . $message['detail']);

					$toSend = 1;
				}

				if ($toSend == 1 && $id_msg = create_user_message($marchand['id'], $message['message'], $message['detail']))
				{
					// On assigne le nouveau message au user
					$query = "INSERT INTO message_has_mobileuser SET `mobileuser_id`='"
					. mysql_real_escape_string($mobile_user['id'])
					. "', `date_creation`=Now(), `message_id`='"
					. mysql_real_escape_string($id_msg)
					. "'";

					doPrint("</br>assignMessageToUser:: " . $query);

					$result = mysql_query($query);

					$pushdata['ids'] = array();
					array_push($pushdata['ids'], "38");
					array_push($pushdata['ids'], "39");
					$pushdata['idsclient'] = array();
					array_push($pushdata['idsclient'], strval($mobile_user['id']));
					$pushdata['message'] = $message['message'] . " - " . $message['detail'];
					$pushdata['OS'] = "iOS_dev";

					update_last_notif($marchand['id'], $mobile_user['id']);
				}

				else $toSend = 0;
			}

			// FIN DE L'AFFINAGE
			doPrint("</br>Fin de l'affinage -> toSend = " . $toSend . " and push data = " . json_encode($pushdata));

			// Envoi du push
			if($toSend == 1)
			{
				// PROCESS L'ENVOI
				$jsonPush = json_encode($pushdata);
				postRequest("http://push.youfid.fr/serverpush/save/sendpushtousers.php", $jsonPush);

////////////
				doLog("</br>Envoi push : " . json_encode($pushdata) . "</br>");
			}
		}

	}
	else
	{
		$error = true;
		$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	}

	/// Gestion d'erreur
	if ($error == true)
	{
		$status = "error";

		$jsonResult['status'] = $status;
		$jsonResult['message'] = $errorMsg;
		echo(json_encode(array_map_utf8_encode($jsonResult)));
	}
	else
	{
		$status = "ok";

		$jsonResult['status'] = $status;
		echo(json_encode(array_map_utf8_encode($jsonResult)));
	}

	doLog("Response::" . json_encode(array_map_utf8_encode($jsonResult)));

