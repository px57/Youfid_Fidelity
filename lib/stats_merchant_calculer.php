<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$tbl_mobileuser = "mobileuser";
	$tbl_transaction = "transaction";
	$tbl_marchands = "marchand";
	
	$tbl_msg_had_mbu = "message_has_mobileuser";
	$tbl_msg = "message";
	$tbl_authentification = "authentification";
	$tbl_push = "histo_push";
	
	/// Id Marchand
	$merchant_id = $_SESSION['selector'];
	
	$error_msg = stats_calculer();
	echo($error_msg);

	function stats_calculer()
	{
		global $logger;
		
		$form_values = check_form();
		
		if (!$form_values)
			return "Erreur avec le formulaire";
		
		$result = "";
		switch($form_values['filterlist'])
		{
			case 1:
				$result = stats_nb_user($form_values);
				break;
			case 2:
				$result = stats_nb_scans($form_values);
				break;
			case 3:
				$result = stats_nb_cadeaux($form_values);
				break;
			case 4:
				$result = stats_panier_moyen($form_values);
				break;
			case 5:
				$result = stats_facture_moyen($form_values);
				break;
		}
		
		return $result;
	}

	function stats_facture_moyen($form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			/// Cas numero 1 : super_marchand
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$res = stats_facture_moyen_super_marchand($form_values, $merchant_id);
			/// Cas numero 2 : super_marchand
			else
				$res = stats_facture_moyen_marchand($form_values, $merchant_id);
		}
		
		if ($res == -1)
			return "Erreur lors de l'edition des statistiques";
		
		return "Facture totale par client:" . strval($res) . " Euros";	
	}

	function stats_facture_moyen_super_marchand($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$id_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
			$auth_result = mysql_query($query);
			
			/// On recupere le mobileuser
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					if ((!in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$id_array[$mobi_row['id']] = intval($auth_row['amount']);
					else if ((in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$id_array[$mobi_row['id']] += intval($auth_row['amount']);
					else if ((!in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$id_array[$mobi_row['id']] = intval($auth_row['amount']);
					else if ((in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$id_array[$mobi_row['id']] += intval($auth_row['amount']);
				}
			}	
		}
		$amount = 0;
		if (count($id_array) == 0)
			return 0;
		else
		{
			foreach ($id_array as $key => $value) {
				$amount += $value;
			}
		}
		
		return $amount / count($id_array);
	}

	function stats_facture_moyen_marchand($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$id_array = array();
		while ($auth_row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
			if ($mobi_row = mysql_fetch_array($result_mobi))
			{
				if ((!in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$id_array[$mobi_row['id']] = intval($auth_row['amount']);
					else if ((in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$id_array[$mobi_row['id']] += intval($auth_row['amount']);
					else if ((!in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$id_array[$mobi_row['id']] = intval($auth_row['amount']);
					else if ((in_array($mobi_row['id'], $id_array)) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$id_array[$mobi_row['id']] += intval($auth_row['amount']);
			}
		}
		
		$amount = 0;
		if (count($id_array) == 0)
			return 0;
		else
		{
			foreach ($id_array as $key => $value)
				$amount += $value;
		}
		
		return $amount / count($id_array);
	}

	function stats_panier_moyen($form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			/// Cas numero 1 : super_marchand
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$res = stats_panier_moyen_super_marchant($form_values, $merchant_id);
			/// Cas numero 2 : super_marchand
			else
				$res = stats_panier_moyen_marchant($form_values, $merchant_id);
		}
		
		if ($res == -1)
			return "Erreur lors de l'edition des statistiques";
		
		return "Montant moyen du panier sur la période:" . strval($res) . " Euros";
	}
	
	function stats_panier_moyen_super_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$count = 0;
		$amount = 0;
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
			$auth_result = mysql_query($query);
			
			/// On recupere le mobileuser
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					if ((substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
					{
						$count += 1;
						$amount += intval($auth_row['amount']);
					}
					else if ((substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
					{
						$count += 1;
						$amount += intval($auth_row['amount']);
					}
				}
			}	
		}
		if ($amount == 0 || $count == 0)
			return 0;
		return $amount / $count;
	}
	
	function stats_panier_moyen_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$id_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
			if ($row_mob = mysql_fetch_array($result_mobi))
			{
				if ((substr($row_mob['qr_code'], 0, 1) == "1") && ($form_values['is_app'] == 1))
				{
					if (in_array($row_mob['id'], $id_array))
						$id_array[$row_mob['id']] += $row_mob['amount'];
					else
						$id_array[$row_mob['id']] = $row_mob['amount'];
				}
				else if ((substr($row_mob['qr_code'], 0, 1) == "2") && ($form_values['is_physique'] == 1))	
				{
					if (in_array($row_mob['id'], $id_array))
						$id_array[$row_mob['id']] += $row_mob['amount'];
					else
						$id_array[$row_mob['id']] = $row_mob['amount'];
				}
			}
		}
		
		//$amount = 0;
		foreach ($id_array as $elem) {
			
		}
		
		if ($amount == 0 || $count == 0)
			return 0;
		return $amount / $count;
	}
	
	function stats_nb_cadeaux($form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			/// Cas numero 1 : super_marchand
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$res = stats_nb_cadeaux_super_marchant($form_values, $merchant_id);
			/// Cas numero 2 : super_marchand
			else
				$res = stats_nb_cadeaux_marchant($form_values, $merchant_id);
		}
		
		if ($res == -1)
			return "Erreur lors de l'edition des statistiques";
		
		return "Nombre de cadeaux distribués sur la période:" . strval($res);
	}

	function stats_nb_cadeaux_super_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$count = 0;
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
			$auth_result = mysql_query($query);
			
			/// On recupere le mobileuser
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				//$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
//				if ($mobi_row = mysql_fetch_array($mobi_result))
//				{
//					if ((substr($mobi_row['qr_code'], 0, 1) == "1") &&
//						($form_values['is_app'] == 1))
//						$count += 1;
//					else if ((substr($mobi_row['qr_code'], 0, 1) == "2") &&
//						($form_values['is_physique'] == 1))
//						$count += 1;
//				}
				
				/* ALEX */
			
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					if ((substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$count += intval($auth_row['nb_cadeaux']);
					else if ((substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$count += intval($auth_row['nb_cadeaux']);
				}
				
				/* ALEX */
			}	
		}
		return $count;
	}

	function stats_nb_cadeaux_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_transaction WHERE `marchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$count = 0;
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
//			if ($row_mob = mysql_fetch_array($result_mobi))
//			{
//				if ((substr($row_mob['qr_code'], 0, 1) == "1") && ($form_values['is_app'] == 1))
//						$count += 1;
//				else if ((substr($row_mob['qr_code'], 0, 1) == "2") && ($form_values['is_physique'] == 1))	
//						$count += 1;
//					
//			}

/* ALEX */
			
			if ($row_mob = mysql_fetch_array($result_mobi))
			{
				if ((substr($row_mob['qr_code'], 0, 1) == "1") && ($form_values['is_app'] == 1))
						$count += intval($row['nb_cadeaux']);
				else if ((substr($row_mob['qr_code'], 0, 1) == "2") && ($form_values['is_physique'] == 1))	
						$count += intval($row['nb_cadeaux']);
					
			}
			
/* ALEX */
		}
		return $count;
	}

	function stats_nb_scans($form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			/// Cas numero 1 : super_marchand
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$res = stats_nb_scans_super_marchant($form_values, $merchant_id);
			/// Cas numero 2 : super_marchand
			else
				$res = stats_nb_scans_marchant($form_values, $merchant_id);
		}
		
		if ($res == -1)
			return "Erreur lors de l'edition des statistiques";
		
		return "Nombre de scans trouvés sur la période:" . strval($res);
	}

	function stats_nb_scans_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_authentification WHERE `marchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$count = 0;
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
			if ($row_mob = mysql_fetch_array($result_mobi))
			{
				if ((substr($row_mob['qr_code'], 0, 1) == "1") && ($form_values['is_app'] == 1))
						$count += 1;
				else if ((substr($row_mob['qr_code'], 0, 1) == "2") && ($form_values['is_physique'] == 1))	
						$count += 1;
					
			}
		}
		return $count;
	}
	
	function stats_nb_scans_super_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$count = 0;
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_authentification WHERE `marchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
			$auth_result = mysql_query($query);
			
			/// On recupere le mobileuser
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				//$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					if ((substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						$count += 1;
					else if ((substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						$count += 1;
				}
			}	
		}
		return $count;
	}
	
	function stats_nb_user($form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		/// Cas numero 1 : super_marchand
		if ($row = mysql_fetch_array($result))
		{
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$res = stats_nb_user_super_marchant($form_values, $merchant_id);
			else
				$res = stats_nb_user_marchant($form_values, $merchant_id);
		}
		
		/*if ($row = mysql_fetch_array($result) && $row['supermarchand_id'] == "-1")
			$res = stats_nb_user_super_marchant($form_values, $merchant_id);
		else if ($row = mysql_fetch_array($result))
			$res = stats_nb_user_marchant($form_values, $merchant_id);*/
		
		if ($res == -1)
			return "Erreur lors de l'edition des statistiques";
		
		return "Nombre d'utilisateurs trouvés sur la période:" . strval($res);
	}

	/// Retourne nb_user pour un marchand
	function stats_nb_user_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_authentification WHERE `marchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$id_array = array();		
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
			if ($row_mob = mysql_fetch_array($result_mobi))
			{
				if (!in_array($row_mob['id'], $id_array) && (substr($row_mob['qr_code'], 0, 1) == "1") &&
					($form_values['is_app'] == 1))
						array_push($id_array, $row_mob['id']);
				else if (!in_array($row_mob['id'], $id_array) && (substr($row_mob['qr_code'], 0, 1) == "2") &&
					($form_values['is_physique'] == 1))	
						array_push($id_array, $row_mob['id']);
			}
		}
		return count($id_array);
	}

	/// Retourne nb_user pour un super_marchand
	function stats_nb_user_super_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$id_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_authentification WHERE `marchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
			$auth_result = mysql_query($query);
			
			/// On recupere le mobileuser
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				//$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					if (!in_array($mobi_row['id'], $id_array) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						array_push($id_array, $mobi_row['id']);
					else if (!in_array($mobi_row['id'], $id_array) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						array_push($id_array, $mobi_row['id']);
				}
			}	
		}
		return count($id_array);
	}

	function check_form()
	{
		$form_values = array();
		
		if(isset($_POST['date_start']) && !empty($_POST['date_start']))
			$form_values['date_start'] = $_POST['date_start'];
		else
			return FALSE;
		
		if(isset($_POST['date_end']) && !empty($_POST['date_end']))
			$form_values['date_end'] = $_POST['date_end'];
		else
			return FALSE;
		
		if(isset($_POST['filterlist']) && !empty($_POST['filterlist']))
			$form_values['filterlist'] = $_POST['filterlist'];
		else
			return FALSE;
		
		if(isset($_POST['is_app']) && !empty($_POST['is_app']))
			$form_values['is_app'] = 1;
		else
			$form_values['is_app'] = 0;
		
		if(isset($_POST['is_physique']) && !empty($_POST['is_physique']))
			$form_values['is_physique'] = 1;
		else
			$form_values['is_physique'] = 0;
		
		//echo ("physique=" . $form_values['is_physique'] . " isApp=" . $form_values['is_app']);
		
		return $form_values;
	}
?>
