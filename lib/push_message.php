<?php
	if(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT']))
		$BASE_DIR = $_SERVER['DOCUMENT_ROOT'];
	else {
		$dir = dirname(__FILE__);
		$tab = split('/', $dir);
		$sub = array_slice($tab, 1, count($tab) - 2);
		$BASE_DIR = '/' . implode('/', $sub);
	}

	require_once($BASE_DIR . "/lib/Logger.class.php");
	require_once($BASE_DIR . "/dev/service/dbLogInfo.php");
	require_once($BASE_DIR . "/dev/service/utils.php");

	if (!isset($logger))
		$logger = new Logger($BASE_DIR . '/logs/');

	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'push_message', $message, Logger::GRAN_MONTH);
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
	
	//do_push_message(62, 158, 140);
	
	function send_push_msg($msg_array)
	{
		$push_array = array();
		
		doLog("////////////////// START");
		doLog(json_encode($msg_array));
		foreach($msg_array as $elem)
		{
			$promo = get_promo($elem['id_msg']);
			$send_to = array();
			
			if ($promo)
			{
				doLog("PromoTitle::" . $promo['message']);
				
				foreach($elem['id_users'] as $id_user)
				{
					doLog("PromoUsers::ID=" . $id_user);
					
					//if (do_push_message($elem['id_marchand'], $id_user, $elem['id_msg']))
						array_push($send_to, strval($id_user));
				}
				
				if ($send_to)
				{
					$push = array();
					$push['msg'] = $promo['message'] . " - " . $promo['detail'];
					$push['send_to'] = $send_to;
					doLog("PROMO TIME: ".$promo['start_date']);					
					//$date1 = new DateTime($promo['start_date']);
					$date_array = split('-', $promo['start_date']);
					//$date1->setDate($date_array[0], $date_array[1], $date_array[2]);
					//$date1->setTime(0, 0, 0);
					//$date2 = new DateTime() ;
					//$date2->setTime(0, 0, 0);
                                       
					$promTimestamp = mktime(0, 0, 0, $date_array[1], $date_array[2], $date_array[0]);
					$nowTimestamp = time();

					doLog("DATE 1: ". date('Y-m-d', $promTimestamp));
					doLog("Date 2: ". date('Y-m-d', $nowTimestamp)) ;
 					doLog("Promo time: ".$promo['start_date']);

                                        if($promTimestamp > $nowTimestamp) {
                                            $push['time'] = $promo['start_date'] . " 11:00:00" ;
					    doLog("push time: " . $push['time']);
					}
					
					array_push($push_array, $push);
				}
			}	
		}
		
		if ($push_array)
			send_promo_as_push($push_array);
	}
	
	function send_promo_as_push($push_array)
	{
		doLog("in_send_promo_as_push");
		
		foreach($push_array as $push)
		{
			doLog("PUSH::" . $push['msg']);
			
			$id_array = array();
			$id_array['ids'] = array();
			array_push($id_array['ids'], "38");
			array_push($id_array['ids'], "39");
			
			$id_array['idsclient'] = $push['send_to'];
			//$id_array['idsclient'] = array();
			//array_push($id_array['idsclient'], $push['send_to']);
			
			
			$id_array['message'] = $push['msg'];
			$id_array['OS'] = "iOS_dev";
			$id_array['time'] = $push['time'] ;
			
			//doLog("Post promo as push for usr=" . $mbusr_id . " promo=" . $promo['id']);
			doLog("//////////////// START!");
			doLog("==> Push request::" . json_encode($id_array));
			postRequest("http://push.youfid.fr/serverpush/save/sendpushtousers.php", json_encode($id_array));
		}
	}
	
	function do_push_message($merchant_id, $mbusr_id, $message_id)
	{
		$promo = get_promo($message_id);
		if (($promo == FALSE) || ($promo['type'] != "promo"))
			return FALSE;
		
		$merchant = get_merchant($merchant_id);
		if ($merchant == FALSE)
			return FALSE;
		
		/// Set Y and X : x= push_non_lu ; Y = push_nouvelle_promo
		//$y = 1;
		//$x = 2;
		
		$y = $merchant['push_nouvelle_promo'];
		$x = $merchant['push_non_lu'];
		
		/// message_has_mbuser
		$message_array = get_message_array($merchant_id, $mbusr_id);
		
		$is_msg_to_send = FALSE;
		if ($message_array)
		{
			//$_y = count($message_array);
			$_x = count_msg_not_read($message_array);
			
			if ($_x <= $x)
				return TRUE;
				//send_promo_as_push($promo, $mbusr_id);
			//doLog("inDoPushMessage::y%y=" . ($_y % $y) . " x%x=" . ($_x % $x));
			//if ((($_y % $y) == 0) && (($_x % $x) == 0))
			else if (($_x > $x) && (($_x % $y) == 0))
				return TRUE;
				//send_promo_as_push($promo, $mbusr_id);
		}
		
		return FALSE;
		//return TRUE;
	}
	
	/*function send_promo_as_push($promo, $mbusr_id)
	{
		$id_array = array();
		$id_array['ids'] = array();
		array_push($id_array['ids'], "38");
		array_push($id_array['ids'], "39");
		$id_array['idsclient'] = array();
		array_push($id_array['idsclient'], strval($mbusr_id));
		$id_array['message'] = $promo['titre'] . " - " . $promo['message'];
		$id_array['OS'] = "iOS_dev";
		
		doLog("Post promo as push for usr=" . $mbusr_id . " promo=" . $promo['id']);
		postRequest("http://picpus.4gsecure.fr/serverpush/save/sendpushtousers.php", json_encode($id_array));
	}*/
	
	/// Compte le nombre de messages ou "has_been_read" = 0
	function count_msg_not_read($message_array)
	{
		$count = 0;
		foreach ($message_array as $promo)
		{
			if ($promo['has_been_read'] == "0")
				$count += 1;
		}
		
		return $count;
	}
	
	/// Obtiens un tableau des "message_has_mobileuser" en rapport avec un marchand et un mobileuser
	function get_message_array($merchant_id, $mbusr_id)
	{
		global $tbl_msg, $tbl_msg_had_mbu;
		
		$query = "SELECT * FROM $tbl_msg WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;
		
		$message_array = array();
		while ($row = mysql_fetch_array($result))
		{
			if ($row['type'] == "promo")
			{
				$query = "SELECT * FROM $tbl_msg_had_mbu WHERE `message_id`='"
					. mysql_real_escape_string($row['id'])
					. "' AND `mobileuser_id`='"
					. mysql_real_escape_string($mbusr_id)
					. "'";
				
				$promo_result = mysql_query($query);
				
				if ($result != FALSE && mysql_num_rows($promo_result))
					array_push($message_array, mysql_fetch_array($promo_result));
			}
		}
		
		return $message_array;
	}
	
	function get_promo($message_id)
	{
		global $tbl_msg;
		
		$query = "SELECT * FROM $tbl_msg WHERE `id`='"
			. mysql_real_escape_string($message_id)
			. "'";
			
		$result = mysql_query($query);
		
		/// Si la requete echoue ou si aucune promo n'est trouvé ==> return FALSE
		if ($result == FALSE || !mysql_num_rows($result))
			return FALSE;
			
		/// On retourne la promo
		return mysql_fetch_array($result);
	}
	
	function get_merchant($merchant_id)
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		/// Si la requete echoue ou si aucun marchand n'est trouvé ==> return FALSE
		if ($result == FALSE || !mysql_num_rows($result))
			return FALSE;
		
		/// On retourne le marchand
		return mysql_fetch_array($result);
	}

?>
