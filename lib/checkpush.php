<?php

/*************************************************************************/
/*													       				 */
/*   This is to check what push messages have to be sent and send them   */
/*														   				 */
/*************************************************************************/

	require_once("Logger.class.php");
	require_once("../dev/service/dbLogInfo.php");
	require_once("../dev/service/utils.php");
	require_once("../lib/db_functions2.php");

	if (!isset($logger))
		$logger = new Logger('../logs/');

	function doLog($message)
	{
		echo $message;
		
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'checkPush', $message, Logger::GRAN_MONTH);	
		
	}
	
	function doPrint($message)
	{
		//echo $message;
	}
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$tbl_mobileuser = "mobileuser";
	$tbl_transaction = "transaction";
	$tbl_marchands = "marchand";
	$tbl_jour = "jour";
	$tbl_push = "pushgeoloc";
	$tbl_histo_push = "histo_push";
	$tbl_msg_had_mbu = "message_has_mobileuser";
	$tbl_marchand_had_mbu = "marchand_has_mobileuser";
	
	$tbl_msg = "message";
	$tbl_authentification = "authentification";
	
	$loyalty_access;
	$is_loyalty_logged = FALSE;
	
	$array_more = array();
	
////
	doLog("====================== BEGIN SENDIND PUSH =========================");
	doLog("<br/>Start time = ".date("Y-m-d H:i:s"));
	
	$timestart=microtime(true);
	
	checkPush();
	
	$timeend=microtime(true);
	$scripttime=$timeend-$timestart;	
	$page_load_time = number_format($scripttime, 3);
////
	doLog("</br>Script checkpush execute en " . $page_load_time . " secondes");
	doLog("</br>======================= END SENDIND PUSH ==========================");
	
	function time_to_minute($time)
	{
		$hours = substr($time, 0, 2);
		$hours = intval($hours);
		
		$minutes = substr($time, 3, 2);
		$minutes = intval($minutes);
		
		$current_time = ($hours * 60) + $minutes;
		return $current_time; 
	}
	
	function get_jour_id($jour)
	{
		global $tbl_jour;
		
		$query = "SELECT * FROM $tbl_jour WHERE `nom`='"
			. mysql_real_escape_string($jour)
			. "'";
		
		$res = -1;
		
		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result))
			$res = $row['id'];
		return $res;
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//																										//
	//    Récupération de tous les messages qui respectent les règles d'envoi du push pour les marchands	//
	//																										//
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	function get_message_to_push($id_jour, $current_time)
	{
		$push_array = array();		
////////
		doPrint("</br>In getMessageToPush");
		
		$push_query = "SELECT * FROM pushgeoloc p JOIN marchand m"
			. " WHERE p.jour_id = "
			. mysql_real_escape_string($id_jour)
			. " AND p.is_active = 1 "
			. " AND p.date_debut <= "
			. mysql_real_escape_string($current_time)
			. " AND p.date_fin >= "
			. mysql_real_escape_string($current_time)
			. " AND p.marchand_id = m.id"
			. " AND m.is_push_actif = 1 ";
			
		$push_result = mysql_query($push_query);
		while ($row = mysql_fetch_array($push_result))
		{	
			$message = get_msg($row['msg_last_id']);			
////////////
			doPrint("</br></br>New message = " . $message['message'] . " - " . $message['detail']);
		
			if ($message != FALSE && $message['is_validated'] == "1")
					array_push($push_array, $row);
		}
		return $push_array;
	}
	
	function get_msg($msg_id)
	{
		global $tbl_msg;
		
		$query = "SELECT * FROM $tbl_msg WHERE `id`='"
			. mysql_real_escape_string($msg_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result != FALSE && ($row = mysql_fetch_array($result)))
			return $row;
		return FALSE;
	}
	
	function get_merchant($merchant_id)
	{
		global $tbl_marchands;
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result))
			return $row;
		return FALSE;
	}
	
	
	function update_last_notif($merchant_id, $mbu_id)
	{
		global $tbl_marchand_had_mbu;
		
		$tbl_histo = "histo_push";
		
		$query = "UPDATE $tbl_marchand_had_mbu SET `last_notif`=current_timestamp() WHERE `mobileuser_id`='"
			. mysql_real_escape_string($mbu_id)
			. "' AND `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		/// Update histo-Push
		
		$query = "INSERT INTO $tbl_histo SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($mbu_id)
			. "', `push_date`=current_timestamp()";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		return TRUE;
	}
	
	function doLoyaltyLogin()
	{	
		$req_login = array(
        	"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        	"login" => "youfid",
        	"password" => "youfid"
			);
				
		$req_login = json_encode($req_login);
		$result = postRequest($url_loyalty . "services/user/login", $req_login);
					
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
	
	/// NEW
	function subscribe_user($mobileuser_id, $merchant_id, $sm_id)
	{
		global $loyalty_access, $is_loyalty_logged, $tbl_marchand_had_mbu;
		
		$query = "INSERT INTO $tbl_marchand_had_mbu SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($mobileuser_id)
			."', `nb_use`='0'";
					
		$result = mysql_query($query); 
				
		if ($result != FALSE && $sm_id != -1)
		{
			subscribe_user_to_sm($merchant_id, $mobileuser_id);
			return TRUE;
		}
		return FALSE;
	}
	


	
	/// Ajoute une ligne a mhmbuser_row
	function add_mhmbuser_row($mbuser_id, $push)
	{
		global $tbl_msg_had_mbu;
		
		$query = "INSERT INTO $tbl_msg_had_mbu SET `mobileuser_id`='"
			. mysql_real_escape_string($mbuser_id)
			. "', `date_creation`=Now(), `message_id`='"
			. mysql_real_escape_string($push['msg_last_id'])
			. "'";
			
		$result = mysql_query($query);
	}
	
	
	//////////////////////////
	//						//
	//    Filtre clients   	//
	//						//
	//////////////////////////
	
	function get_ids_array_v3($push_array)
	{
		global $tbl_mobileuser, $tbl_marchand_had_mbu, $array_more;
		$push_list = array();
		
////////
		doPrint("</br>In getIdsArrayV3");
		
		// On parcourt la liste des messages pushs à envoyer
		foreach($push_array as $push)
		{
////////////
			doPrint("</br>New push : " . $push['titre'] . " - " . $push['message']);
			
			$id_array = array();
			$id_array['ids'] = array();
			array_push($id_array['ids'], "38");
			array_push($id_array['ids'], "39");
			$id_array['idsclient'] = array();
			$id_array['message'] = $push['titre'] . " - " . $push['message'];
			$id_array['OS'] = "iOS_dev";
			
			////////////////////////////////////  CALCUL DISTANCE SQL  ///////////////////////////////////////////////////////
			//																												//
			// SELECT * FROM mobileuser WHERE 6371*acos(sin(lattitude*pi()/180)*sin((48.8351266)*pi()/180) 					//
			// + cos(lattitude*pi()/180)*cos((48.8351266)*pi()/180)*cos(((2.4035724)-longitude)*pi()/180)) * 1000 <= 500	//
			//																												//
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			
			// Récupération des utilisateurs répondant aux conditions requises pour recevoir le push géolocalisé
			// P.S. Les infos du marchand sont comprises dans le tableau push
			$query = "SELECT * FROM mobileuser m JOIN $tbl_marchand_had_mbu mhm"
			. " WHERE mhm.marchand_id = "
			. mysql_real_escape_string($push['id'])
			. " AND mhm.mobileuser_id = m.id"
			. " AND ("
			. "6371*acos(sin(m.lattitude*pi()/180)*sin(("
			.floatval($push['latittude'])
			. ")*pi()/180) + cos(m.lattitude*pi()/180)*cos(("
			.floatval($push['latittude'])
			. ")*pi()/180)*cos(("
			.floatval($push['longitude'])
			. "-m.longitude)*pi()/180))"
			. ") * 1000 <= "
			. mysql_real_escape_string($push['distance_push']);
						
			$result = mysql_query($query);

////////////
			doPrint("</br></br>Les utilisateurs suivants sont dans la zone du push geolocalise du marchand " . $push['name'] . ":");
			
			while ($mobile_user = mysql_fetch_array($result))
			{	
////////////////
				doPrint("</br>User " . $mobile_user['prenom']." ".$mobile_user['nom']);

				// ON AFFINE

				// Récupération de la date du dernier push géolocalisé de ce marchand pour ce user
				$lastnotif = strtotime($mobile_user['last_notif']);
				
				// Si le user a déjà reçu un push géolocalisé
				if ($lastnotif > 0)
				{
////////////////////////
					doPrint(" a deja recu un push de sa part");

					// Calcul du temps écoulé depuis le dit push géolocalisé
					$current_time = time("Y-m-d H:i:s");
					$last_notif_time = $lastnotif + intval($push['delay_push']) * 60;
					
					// Si le user n'a pas reçu de push de la part du marchand au cours des 15 derniers jours
					if ($current_time > $last_notif_time)
					{
////////////////////////////
						doPrint(" il y'a plus de 15 jours");

						// 1.a) Si le user a déjà reçu une promo ciblée de la part du marchand:
						if($old_promo = check_has_promo($push['id'], $mobile_user['id']))
						{
////////////////////////////////
							doPrint("</br>>>>Le user a deja recu une promo ciblee de la part du marchand: "
							. $old_promo['titre'] . " " . $old_promo['message']);
							
							update_last_notif($mobile_user['marchand_id'], $mobile_user['mobileuser_id']);
							
							if ($id_msg = create_user_message($push['marchand_id'], $old_promo))
							{
								$old_promo['msg_last_id'] = $id_msg;
								add_mhmbuser_row($mobile_user['id'], $old_promo);
								
								$sid_array = array();
								$sid_array['ids'] = array();
								array_push($sid_array['ids'], "38");
								array_push($sid_array['ids'], "39");
								$sid_array['idsclient'] = array();
								array_push($sid_array['idsclient'], strval($mobile_user['id']));
								$sid_array['message'] = $old_promo['titre'] . " - " . $old_promo['message'];
								$sid_array['OS'] = "iOS_dev";
								
								array_push($array_more, $sid_array);
							}
						}
							
						// 1.b) Si le user n'a jamais reçu de promo ciblée de la part du marchand:
						else
						{
////////////////////////////////
							doPrint("</br>>>>Le user n'a jamais recu de promo ciblee de la part du marchand");

							array_push($id_array['idsclient'], strval($mobile_user['id']));
						
							update_last_notif($mobile_user['marchand_id'], $mobile_user['mobileuser_id']);
							
							// CREATE MSG
							if ($id_msg = create_user_message($push['marchand_id'], $push))
							{
								$push['msg_last_id'] = $id_msg;
								add_mhmbuser_row($mobile_user['id'], $push);
							}
						}
					}
////////////////////////
					else doPrint(" il y'a moins de 15 jours");
				}
				
				// Si le user n'a jamais reçu de push géolocalisé de la part de ce marchand
				else
				{
////////////////////////
					doPrint(" n'a jamais recu de push geolocalise de sa part");
				
					array_push($id_array['idsclient'], strval($mobile_user['id']));
							
					update_last_notif($mobile_user['marchand_id'], $mobile_user['mobileuser_id']);
					
					// CREATE MSG
					if ($id_msg = create_user_message($push['marchand_id'], $push))
					{
						$push['msg_last_id'] = $id_msg;
						add_mhmbuser_row($mobile_user['id'], $push);
					}
				}
			
				// FIN DE L'AFFINAGE
				
			}

			if ($id_array['idsclient'])
				array_push($push_list, $id_array);
		}

		return $push_list;
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
	
	function create_user_message($merchant_id, $push)
	{
		global $tbl_msg;
		
		$time = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+ 15, date("Y"));
		$date_end = date("Y-m-d", $time);
		
		$query = "INSERT INTO $tbl_msg SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `type`='promo', `points`='0', `message`='"
			. mysql_real_escape_string($push['titre'])
			. "', `detail`='"
			. mysql_real_escape_string($push['message'])
			. "', `start_date`=Now(), `finish_date`='$date_end', `is_validated`='1'";
			
		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;
		
		return mysql_insert_id();	
	}
	
	//////////////////////////
	//						//
	//    ENVOI DU PUSH		//
	//						//
	//////////////////////////
	
	function sendPush($push_array)
	{		
		$date = date("d-m-Y");
		$heure = date("H:i");
		
		foreach($push_array as $push_item)
		{
////////////		
			doLog("</br>Envoi push : " . json_encode($push_item));	
			
			postRequest("http://push.youfid.fr/serverpush/save/sendpushtousers.php", json_encode($push_item));	
		}
	}
	
	function checkPush()
	{
		global $array_more;
		
		$tab_jours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
		$current_day = $tab_jours[date('w', mktime(0,0,0,date('m'),date('d'),date('Y')))];
		$id_jour = get_jour_id($current_day);
		
		$current_time = date("H:i:s");
		$current_time = time_to_minute($current_time);
		
		// Récupération des messages à envoyer
		$push_array = get_message_to_push($id_jour, $current_time);
		
		// Récupération des users à qui envoyer les messages
		$push_array = get_ids_array_v3($push_array);
		
		foreach ($array_more as $elem)
			array_push($push_array, $elem);
////////		
		doPrint("</br>InCheckPush::ArrayPush::elem::" . count($push_array));
		
		if ($push_array)
			sendPush($push_array);
	}
?>

