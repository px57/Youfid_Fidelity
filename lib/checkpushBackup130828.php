<?php

	require_once("Logger.class.php");
	require_once("../dev/service/dbLogInfo.php");
	require_once("../dev/service/utils.php");
	require_once("../lib/db_functions2.php");
	//require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/db_functions.php");

	if (!isset($logger))
		$logger = new Logger('../logs/');

	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'checkPush', $message, Logger::GRAN_MONTH);
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
	
	echo("\n====================== BEGIN SENDIND PUSH @ " . date("Y-m-d H:i:s") . " ==================================\n");
	checkPush();
	echo("\n====================== END SENDIND PUSH @ " . date("Y-m-d H:i:s") . " ==================================\n");
	
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
	
	function get_message_to_push($id_jour, $current_time)
	{
		global $tbl_push, $tbl_marchands;
		
		//doLog("in_getMessageToPush::idjour=" . $id_jour . " current_time=" . $current_time);
		
		$push_query = "SELECT * FROM $tbl_push WHERE `jour_id`='"
			. mysql_real_escape_string($id_jour)
			. "' AND `is_active`='1' AND `date_debut`<='"
			. mysql_real_escape_string($current_time)
			. "' AND `date_fin`>='"
			. mysql_real_escape_string($current_time)
			. "'";
			
		$push_result = mysql_query($push_query);
		//echo($push_query);
		
		//doLog("in_getMessageToPush::query=" . $push_query);
		$push_array = array();
		while ($row = mysql_fetch_array($push_result))
		{
			$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
				. mysql_real_escape_string($row['marchand_id'])
				. "'";
				
			$result = mysql_query($query);
			
			$message = get_msg($row['msg_last_id']);
			
			//echo("Message to check=" . $message['message']);
			
			if (($merchant_row = mysql_fetch_array($result)) && ($message != FALSE))
			{
				if (($merchant_row['is_push_actif'] == "1") && ($merchant_row['is_active'] == "1") && ($message['is_validated'] == "1"))
				{
					//echo("MESSAGEOK:" . $message['message']);
					array_push($push_array, $row);
					//doLog("Push Add::Push_name=" . $row['titre']);
				}
			}
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
	
	function get_mobile_user($mbu_id)
	{
		global $tbl_mobileuser;
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			. mysql_real_escape_string($mbu_id)
			. "'";
			
		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result))
			return $row;
		return FALSE;
	}
	
	/// check la derniere fois que ce user a ete push ainsi que la distance
	function is_user_want_push($merchant, $mobile_user, $last_notif, $distance)
	{
		//doLog("////////////in_user_want_push::ID=" . $mobile_user['id']);
		$res = TRUE;
		
		//doLog("LastNotif (SQL)::" . $last_notif);
		
		// en minutes
		$cooldown = intval($merchant['delay_push']);
		
		//$last_notif_time = time($last_notif);
		//$last_notif_time = new DateTime($last_notif);
		
		if ($last_notif != -1)
		{
			$last_notif_time = strtotime($last_notif);
			$current_time = time("Y-m-d H:i:s");
			//doLog("LastNotifTime::" . $last_notif_time);
			$last_notif_time = $last_notif_time + intval($merchant['delay_push']) * 60;
			//doLog("LastNotifTime + interval::" . $last_notif_time);
			
			if ($current_time < $last_notif_time)
			{
				//doLog("FALSE because of time::" . $current_time . ", " . $last_notif_time . "::diff=" . ($last_notif_time - $current_time) / 60);
				$res = FALSE;
			}
		}
		
		/// Conversion km to m:
		$distance = $distance * 1000;
		
		//floatval()
		if ($distance >= intval($merchant['distance_push']))
		{
			//doLog("FALSE because of distance");
			$res = FALSE;
		}
		
		
		
		return $res;
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
		//global $logger;
		
		$req_login = array(
        	"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        	"login" => "youfid",
        	"password" => "youfid"
			);
				
		$req_login = json_encode($req_login);
					
		$result = postRequest("http://localhost:8080/loyalty-1.0/services/user/login", $req_login);
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
	
	/// NEW
	function subscribe_user($mobile_user, $merchant)
	{
		global $loyalty_access, $is_loyalty_logged, $tbl_marchand_had_mbu;
		
		$query = "INSERT INTO $tbl_marchand_had_mbu SET `marchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($mobile_user['id'])
			."', `nb_use`='0'";
					
		$result = mysql_query($query); 
				
		if ($result != FALSE)
		{
			subscribe_user_to_sm($merchant['id'], $mobile_user['id']);
			return TRUE;
		}
		return FALSE;
	}
	
	/// OLD
	/*function subscribe_user($mobile_user, $merchant)
	{
		global $loyalty_access, $is_loyalty_logged, $tbl_marchand_had_mbu;
		
		if (!$is_loyalty_logged)
			$loyalty_access = doLoyaltyLogin();
		
		if ($loyalty_access != FALSE)
		{
			//doLog("subscribe REQUEST");
			
			$is_loyalty_logged = TRUE;
		
			$json_insri = '{
			     "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
			     "wsAccessToken" : "' . $loyalty_access->wsAccess->wsAccessToken . '",
			     "mobileUserPublicId" : "'. $mobile_user['public_id']  . '",
			     "applicationPublicId" : "' .  $merchant['application_id'] . '",
			     "points" : 0
			     }';
			
			$inscri_url = "http://localhost:8080/loyalty-1.0/services/mobileuser";
			$result_inscri =  postRequest($inscri_url, $json_insri);
			
			$res = json_decode($result_inscri);
			
			if (isset($res->error))
				$youfid_error = $res->error;
			else
				return FALSE;
			
			if (isset($youfid_error->messages[0]) && $youfid_error->messages[0] == "OK")
			{
				$query = "INSERT INTO $tbl_marchand_had_mbu SET `marchand_id`='"
					. mysql_real_escape_string($merchant['id'])
					. "', `mobileuser_id`='"
					. mysql_real_escape_string($mobile_user['id'])
					."', `nb_use`='0'";
					
				$result = mysql_query($query); 
				
				if ($result != FALSE)
				{
					subscribe_user_to_sm($merchant['id'], $mobile_user['id']);
					return TRUE;
				}
			}
		}
		return FALSE;
	}*/
	
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
		
		//doLog("inAdd_mhmbuser_row::QUERY=" . $query);
	}
	
	/// Cette fois, on y va franchement et on tappe sur tous les clients!
	function get_ids_array_v2($push_array)
	{
		global $tbl_mobileuser, $tbl_marchand_had_mbu;
		
		$push_list = array();
		
		//doLog("inGet_ids_array_v2");
		foreach($push_array as $push)
		{
			$id_array = array();
			$id_array['ids'] = array();
			array_push($id_array['ids'], "38");
			array_push($id_array['ids'], "39");
			$id_array['idsclient'] = array();
			$id_array['message'] = $push['titre'] . " - " . $push['message'];
			$id_array['OS'] = "iOS_dev";
			
			$merchant = get_merchant($push['marchand_id']);
			
			$query = "SELECT * FROM $tbl_mobileuser";
			
			//doLog("Query=" . $query);
			$result = mysql_query($query);
			
			while (($row = mysql_fetch_array($result)) && $merchant && ($merchant['is_push_actif'] == "1"))
			{
				$mobile_user = get_mobile_user($row['id']);
				
				//doLog("UserID=" . $row['id']);
				
				if ($mobile_user && !empty($mobile_user['lattitude']) && !empty($mobile_user['longitude']))
				{
					$query = "SELECT * FROM $tbl_marchand_had_mbu WHERE `marchand_id`='"
						. mysql_real_escape_string($merchant['id'])
						. "' AND `mobileuser_id`='"
						. mysql_real_escape_string($mobile_user['id'])
						. "'";
					
					//doLog($query);
					
					$mhmbu_result = mysql_query($query);
					/// Inscription sur loyalty
					if (!mysql_num_rows($mhmbu_result))
						subscribe_user($mobile_user, $merchant);
					
					$query = "SELECT * FROM $tbl_marchand_had_mbu WHERE `marchand_id`='"
						. mysql_real_escape_string($merchant['id'])
						. "' AND `mobileuser_id`='"
						. mysql_real_escape_string($mobile_user['id'])
						. "'";
					
					$mhmbu_result = mysql_query($query);
					if ($mhmbu_row = mysql_fetch_array($mhmbu_result))
					{
						$distance = distance($merchant['latittude'], $merchant['longitude'], $mobile_user['lattitude'], $mobile_user['longitude']);
						
						//doLog("Distance=" . $distance);
					
						if (is_user_want_push($merchant, $mobile_user, $mhmbu_row['last_notif'], $distance) == TRUE)
						{
							//doLog("User_want_push OK!!!::push_userid:" . $mobile_user['id']);
							//array_push($id_array['ids'], "38");
							array_push($id_array['idsclient'], strval($mobile_user['id']));
							
							update_last_notif($mhmbu_row['marchand_id'], $mhmbu_row['mobileuser_id']);
							add_mhmbuser_row($mhmbu_row['mobileuser_id'], $push);
						}
					}
				}
			}

			if ($id_array['idsclient'])
			{
				//doLog("////////////arraypush for send");
				array_push($push_list, $id_array);
			}
		}

		return $push_list;
	}
	
	/// Cette fois, on y va franchement et on tappe sur tous les clients!
	function get_ids_array_v3($push_array)
	{
		global $tbl_mobileuser, $tbl_marchand_had_mbu, $array_more;
		
		$push_list = array();
		
		//doLog("inGet_ids_array_v2");
		foreach($push_array as $push)
		{
			$id_array = array();
			$id_array['ids'] = array();
			array_push($id_array['ids'], "38");
			array_push($id_array['ids'], "39");
			$id_array['idsclient'] = array();
			$id_array['message'] = $push['titre'] . " - " . $push['message'];
			$id_array['OS'] = "iOS_dev";
			
			$merchant = get_merchant($push['marchand_id']);
			
			$query = "SELECT * FROM $tbl_mobileuser";
			
			//doLog("Query=" . $query);
			$result = mysql_query($query);
			
			while (($row = mysql_fetch_array($result)) && $merchant && ($merchant['is_push_actif'] == "1"))
			{
				$mobile_user = get_mobile_user($row['id']);
				
				if ($mobile_user && !empty($mobile_user['lattitude']) && !empty($mobile_user['longitude']))
				{
					$query = "SELECT * FROM $tbl_marchand_had_mbu WHERE `marchand_id`='"
						. mysql_real_escape_string($merchant['id'])
						. "' AND `mobileuser_id`='"
						. mysql_real_escape_string($mobile_user['id'])
						. "'";
					
					//$mhmbu_result = mysql_query($query);
					
					$mhmbu_result = mysql_query($query);
					if ($mhmbu_row = mysql_fetch_array($mhmbu_result))
					{
						$distance = distance($merchant['latittude'], $merchant['longitude'], $mobile_user['lattitude'], $mobile_user['longitude']);
						
						//doLog("Une ligne existe");
						
						if ((is_user_want_push($merchant, $mobile_user, $mhmbu_row['last_notif'], $distance) == TRUE) 
							&& (!check_has_promo($push['marchand_id'], $mhmbu_row['mobileuser_id'])))
						{
							array_push($id_array['idsclient'], strval($mobile_user['id']));
							
							update_last_notif($mhmbu_row['marchand_id'], $mhmbu_row['mobileuser_id']);
							/// CREATE MSG
							if ($id_msg = create_user_message($push['marchand_id'], $push))
							{
								$push['msg_last_id'] = $id_msg;
								add_mhmbuser_row($mobile_user['id'], $push);
							}
							
							//add_mhmbuser_row($mhmbu_row['mobileuser_id'], $push);
						}
						/// On renvois le contenu de la derniere promo
						else if (is_user_want_push($merchant, $mobile_user, $mhmbu_row['last_notif'], $distance) == TRUE)
						{
							$old_promo = get_old_promo($mhmbu_row['marchand_id'], $mhmbu_row['mobileuser_id']);
							
							if ($old_promo)
							{
								update_last_notif($mhmbu_row['marchand_id'], $mhmbu_row['mobileuser_id']);
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
							
						}
					}
					/// User non inscrit
					else
					{
						$distance = distance($merchant['latittude'], $merchant['longitude'], $mobile_user['lattitude'], $mobile_user['longitude']);
						
						if (is_user_want_push($merchant, $mobile_user, -1, $distance) == TRUE)
						{
							/// Inscription sur loyalty
							if (!mysql_num_rows($mhmbu_result))
								subscribe_user($mobile_user, $merchant);
							
							/*$query = "SELECT * FROM $tbl_marchand_had_mbu WHERE `marchand_id`='"
								. mysql_real_escape_string($merchant['id'])
								. "' AND `mobileuser_id`='"
								. mysql_real_escape_string($mobile_user['id'])
								. "'";*/
								
							array_push($id_array['idsclient'], strval($mobile_user['id']));
							
							update_last_notif($push['marchand_id'], $mobile_user['id']);
							/// CREATE MSG
							if ($id_msg = create_user_message($push['marchand_id'], $push))
							{
								$push['msg_last_id'] = $id_msg;
								add_mhmbuser_row($mobile_user['id'], $push);
							}
						}
					}
				}
			}

			if ($id_array['idsclient'])
			{
				//doLog("////////////arraypush for send");
				array_push($push_list, $id_array);
			}
		}

		return $push_list;
	}
	
	function get_old_promo($marchand_id, $user_id)
	{
		
		global $tbl_msg, $tbl_msg_had_mbu;
		
		$current_msg = FALSE;
		
		$query = "SELECT * FROM $tbl_msg WHERE `marchand_id`='"
			. mysql_real_escape_string($marchand_id)
			. "' AND `type`='promo'"
			. "AND TO_DAYS( start_date ) <= TO_DAYS( NOW() )"
			. "AND TO_DAYS( finish_date ) >= TO_DAYS( NOW() )"
			. "AND is_validated = '1'";
		
		//doLog("old_promo::query=" . $query);
		$result = mysql_query($query);
		
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_msg_had_mbu WHERE `message_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND `mobileuser_id`='"
				. mysql_real_escape_string($user_id)
				. "' ORDER BY `message_id` DESC";
			
			//doLog("old_promo::query=" . $query);
			$msg_result = mysql_query($query);
			
			if ($msg_result != FALSE && $msg_row = mysql_fetch_array($msg_result))
			{
				//if ($current_msg == FALSE)
					$current_msg = $row;
				/*else if ($msg_row['start_date'] >= $current_msg['start_date'])
					$current_msg = $row;*/
				//else
			}
		}
		
		if ($current_msg != FALSE)
		{
			$promo = array();
			
			$promo['titre'] = $current_msg['message'];
			$promo['message'] = $current_msg['detail'];
			
			//doLog("promo=" . $promo['titre'] . " " . $promo['message']);
			return $promo;
		}
		
		/*else
		{
			$promo = array();
			
			$promo['titre'] = "à proximité";
			$promo['message'] = "faux";
			
			//doLog("promo=" . $promo['titre'] . " " . $promo['message']);
			return $promo;
		}*/
		
		//doLog("old_promo::FALSE");
		return FALSE;
	}
	
	/// verifie si le user en question a deja recu une promo de la part du marchand
	function check_has_promo($merchant_id, $user_id)
	{
		global $tbl_msg, $tbl_msg_had_mbu;
		
		$query = "SELECT * FROM $tbl_msg WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		doLog("incheck_has_promo::query=" . $query);
		$result = mysql_query($query);
		
		if ($result == FALSE)
		{
			doLog("erreurdb");
			return FALSE;
		}
		
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_msg_had_mbu WHERE `message_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND `mobileuser_id`='"
				. mysql_real_escape_string($user_id)
				. "'";
				
			$had_mbu_result = mysql_query($query);
			if ($had_mbu_result != FALSE && mysql_num_rows($had_mbu_result))
			{
				doLog("haspromo");
				return TRUE;
			}
		}
		
		doLog("haventpromo");
		return FALSE;
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
	
	/// Get les clients auquel nous allons envoyer les push
	function get_ids_array($push_array)
	{
		global $tbl_marchand_had_mbu;
		
		$push_list = array();
		
		//doLog("inGet_ids_array");
		
		foreach ($push_array as $push)
		{
			$id_array = array();
			$id_array['ids'] = array();
			array_push($id_array['ids'], "38");
			$id_array['idsclient'] = array();
			$id_array['message'] = $push['titre'] . " - " . $push['message'];
			$id_array['OS'] = "iOS_dev";
			
			$merchant = get_merchant($push['marchand_id']);
			
			$query = "SELECT * FROM $tbl_marchand_had_mbu WHERE `marchand_id`='"
				. mysql_real_escape_string($push['marchand_id'])
				. "'";
			
			//doLog("get_ids_array::query=" . $query);
			
			$result = mysql_query($query);
			while (($row = mysql_fetch_array($result)) && $merchant && ($merchant['is_push_actif'] == "1"))
			{
				$mobile_user = get_mobile_user($row['mobileuser_id']);
				//doLog("MobileUserId=" . $row['mobileuser_id']);
				
				if ($mobile_user && !empty($mobile_user['lattitude']) && !empty($mobile_user['longitude']))
				{
					$distance = distance($merchant['latittude'], $merchant['longitude'], $mobile_user['lattitude'], $mobile_user['longitude']);
					
					//doLog("Distance=" . $distance);
					
					if (is_user_want_push($merchant, $mobile_user, $row['last_notif'], $distance) == TRUE)
					{
						//doLog("User_want_push OK!!!::push_userid:" . $mobile_user['id']);
						//array_push($id_array['ids'], "38");
						array_push($id_array['idsclient'], strval($mobile_user['id']));
						
						update_last_notif($row['marchand_id'], $row['mobileuser_id']);
					}
				}
			}
			
			if ($id_array['idsclient'])
			{
				//doLog("////////////arraypush for send");
				array_push($push_list, $id_array);
			}
		}

		return $push_list;
	}
	
	function sendPush($push_array)
	{
		//doLog("in Send push...");
		
		$date = date("d-m-Y");
		$heure = date("H:i");
		echo($date . "::" . $heure .  "::in Send push...");
		print_r($push_array);
		
		foreach($push_array as $push_item)
		{
			//doLog("SENDING...");
			//doLog("http://picpus.4gsecure.fr/serverpush/save/sendpushtousers.php" . " "  .json_encode($push_item));
			
			//postRequest("http://picpus.4gsecure.fr/serverpush/save/sendpushtousers.php", json_encode($push_item));
			postRequest("http://push.youfid.fr/serverpush/save/sendpushtousers.php", json_encode($push_item));
		}	
	}
	
	function checkPush()
	{
		global $array_more;
		
		/*doLog("///////////////////////////////////////////////////////////////////////////////////");
		doLog("START!");
		doLog("///////////////////////////////////////////////////////////////////////////////////");*/
		
		$tab_jours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
		$current_day = $tab_jours[date('w', mktime(0,0,0,date('m'),date('d'),date('Y')))];
		$id_jour = get_jour_id($current_day);
		
		$current_time = date("H:i:s");
		$current_time = time_to_minute($current_time);
		
		$push_array = get_message_to_push($id_jour, $current_time);
		//$push_array = get_ids_array($push_array);
		$push_array = get_ids_array_v3($push_array);
		
		foreach ($array_more as $elem)
			array_push($push_array, $elem);
		
		doLog("InCheckPush::ArrayPush::elem::" . count($push_array));
		
		if ($push_array)
			sendPush($push_array);
		//echo($current_time);
	}
?>
