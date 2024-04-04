<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 
	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');

	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'youfid_master_stats_marchand_export', $message, Logger::GRAN_MONTH);
	}
	
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
	
	$customer_array = array();
	
	$result = stats_export(); 
	
	//echo($error_msg);

	// Nom du fichier final
	$fileName = "marchand-" . date("d") . "_" . date("m") . "_" .date("Y"). ".csv";
	// la variable qui va contenir les données CSV
	$outputCsv = '';
	for ($i = 0; isset($result[$i]); $i += 1)
	{
        //$i++;
        $Row = $result[$i];
        // Si c'est la 1er boucle, on affiche le nom des champs pour avoir un titre pour chaque colonne
        if($i == 0)
        {
            foreach($Row as $clef => $valeur)
                $outputCsv .= trim($clef).';';

            $outputCsv = rtrim($outputCsv, ';');
            $outputCsv .= "\n";
        }

        // On parcours $Row et on ajout chaque valeur à cette ligne
        foreach($Row as $clef => $valeur)
            $outputCsv .= trim($valeur).';';

        // Suppression du ; qui traine à la fin
        $outputCsv = rtrim($outputCsv, ';');

        // Saut de ligne
        $outputCsv .= "\n";

    }
	
	// Entêtes (headers) PHP qui vont bien pour la création d'un fichier Excel CSV
	header("Content-disposition: attachment; filename=".$fileName);
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: application/vnd.ms-excel\n");
	header("Pragma: no-cache");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
	header("Expires: 0");
	
	echo $outputCsv;
	exit();
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////

	function stats_export()
	{
		global $logger, $merchant_id, $customer_array;
		
		$error = TRUE;
		$form_values = check_form();
		$merchant = get_merchant($merchant_id);
		/// gerer !merchant
		/*
		$result[0] = stats_nb_user($form_values);
		$result[1] = stats_nb_scans($form_values);
		$result[2] = stats_nb_cadeaux($form_values);
		$result[3] = stats_panier_moyen($form_values);
		$result[4] = stats_facture_moyen($form_values);
		*/
		
		$id_array = get_customer_list($form_values, $merchant_id);
		
		$customers = array();
		$index = 0;
		
		foreach ($id_array as $id) 
		{
			//echo("user");
			$customer = array();
			
			$customer['Nom'] = getCustomerName($id);
			$customer['Date de dernière authentification'] = getLastAuth($id, $merchant, $form_values);
			$customer["Nombre d'authentification sur la periode"] = getNbAuth($id, $merchant, $form_values);
			$customer["Nombre de cadeaux contractés sur la période"] = getNbCadeaux($id, $merchant, $form_values);
			$customer["Panier moyen"] = getPanierMoyen($id, $merchant, $form_values);
			$customer["Facture totale"] = getFactureTotale($id, $merchant, $form_values);
			
			array_push($customers, $customer);
		}
		//echo("count=" . count($customers));
		return($customers);
	}
	
	function getFactureTotale($id, $merchant, $form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$auth_array = array();
		
		$query = "SELECT * FROM $tbl_transaction WHERE `mobileuser_id`='"
			. mysql_real_escape_string($id)
			. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$auth_result = mysql_query($query);
		
		doLog("query=" . $query);
		
		/// Cas super_marchand
		//if ($merchant['supermarchand_id'] == "-1")
		if ($merchant['is_supermarchand'] == "1")
		{
			/// get la liste des marchands
			$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "'";
			
			$merchant_result = mysql_query($query);
			$merchant_array = array();
			if($merchant_row = mysql_fetch_array($merchant_result))
				array_push($merchant_array, $merchant_row['id']);
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				if (in_array($auth_row['marchand_id'], $merchant_array))
					array_push($auth_array, $auth_row);
			}
		}
		/// Cas d'un marchand simple
		else
		{
			while ($auth_row = mysql_fetch_array($auth_result))
				array_push($auth_array, $auth_row);
		}
		
		$amount = 0;
		foreach ($auth_array as $transaction)
			$amount += intval($transaction['amount']);
		
		return strval($amount);
	}
	
	function getPanierMoyen($id, $merchant, $form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$auth_array = array();
		
		$query = "SELECT * FROM $tbl_transaction WHERE `mobileuser_id`='"
			. mysql_real_escape_string($id)
			. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$auth_result = mysql_query($query);
		
		doLog("query=" . $query);
		
		/// Cas super_marchand
		//if ($merchant['supermarchand_id'] == "-1")
		if ($merchant['is_supermarchand'] == "1")
		{
			/// get la liste des marchands
			$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "'";
			
			$merchant_result = mysql_query($query);
			$merchant_array = array();
			if($merchant_row = mysql_fetch_array($merchant_result))
				array_push($merchant_array, $merchant_row['id']);
			if ($auth_row = mysql_fetch_array($auth_result))
			{
				if (in_array($auth_row['marchand_id'], $merchant_array))
					array_push($auth_array, $auth_row);
			}
		}
		/// Cas d'un marchand simple
		else
		{
			if ($auth_row = mysql_fetch_array($auth_result))
				array_push($auth_array, $auth_row);
		}
		
		$amount = 0;
		
		foreach ($auth_array as $transaction)
			$amount += $transaction['amount'];
		
		if ($amount != 0 && count($auth_array) != 0)
			$amount = $amount / count($auth_array);
		
		return strval($amount);
	}
	
	function getNbCadeaux($id, $merchant, $form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_transaction;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$auth_array = array();
		
		$query = "SELECT * FROM $tbl_transaction WHERE `mobileuser_id`='"
			. mysql_real_escape_string($id)
			. "' AND TO_DAYS(`transaction_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$auth_result = mysql_query($query);
		
		doLog("query=" . $query);
		
		/// Cas super_marchand
		//if ($merchant['supermarchand_id'] == "-1")
		if ($merchant['is_supermarchand'] == "1")
		{
			/// get la liste des marchands
			$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "'";
			
			$merchant_result = mysql_query($query);
			$merchant_array = array();
			if($merchant_row = mysql_fetch_array($merchant_result))
				array_push($merchant_array, $merchant_row['id']);
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				if (in_array($auth_row['marchand_id'], $merchant_array))
					array_push($auth_array, $auth_row);
			}
		}
		/// Cas d'un marchand simple
		else
		{
			while ($auth_row = mysql_fetch_array($auth_result))
				array_push($auth_array, $auth_row);
		}
		
		$nb_cadeaux = 0;
		foreach ($auth_array as $transaction)
			$nb_cadeaux += $transaction['nb_cadeaux'];
		
		
		return strval($nb_cadeaux);
	}
	
	function getNbAuth($id, $merchant, $form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$auth_array = array();
		
		$query = "SELECT * FROM $tbl_authentification WHERE `mobileuser_id`='"
			. mysql_real_escape_string($id)
			. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$auth_result = mysql_query($query);
		
		/// Cas super_marchand
		//if ($merchant['supermarchand_id'] == "-1")
		if ($merchant['is_supermarchand'] == "1")
		{
			/// get la liste des marchands
			$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "'";
			
			$merchant_result = mysql_query($query);
			$merchant_array = array();
			if($merchant_row = mysql_fetch_array($merchant_result))
				array_push($merchant_array, $merchant_row['id']);
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				if (in_array($auth_row['marchand_id'], $merchant_array))
					array_push($auth_array, $auth_row);
			}
		}
		/// Cas d'un marchand simple
		else
		{
			while ($auth_row = mysql_fetch_array($auth_result))
				array_push($auth_array, $auth_row);
		}
		
		return strval(count($auth_array));
	}
	
	function getLastAuth($customerId, $merchant, $form_values)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$auth_array = array();
		
		$query = "SELECT * FROM $tbl_authentification WHERE `mobileuser_id`='"
			. mysql_real_escape_string($customerId)
			. "' AND TO_DAYS(`authent_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$auth_result = mysql_query($query);
		
		/// Cas super_marchand
		//if ($merchant['supermarchand_id'] == "-1")
		if ($merchant['is_supermarchand'] == "1")
		{
			/// get la liste des marchands
			$query = "SELECT * FROM $tbl_marchands WHERE `supermarchand_id`='"
			. mysql_real_escape_string($merchant['id'])
			. "'";
			
			$merchant_result = mysql_query($query);
			$merchant_array = array();
			if($merchant_row = mysql_fetch_array($merchant_result))
				array_push($merchant_array, $merchant_row['id']);
			if ($auth_row = mysql_fetch_array($auth_result))
			{
				if (in_array($auth_row['marchand_id'], $merchant_array))
					array_push($auth_array, $auth_row);
			}
		}
		/// Cas d'un marchand simple
		else
		{
			if ($auth_row = mysql_fetch_array($auth_result))
				array_push($auth_array, $auth_row);
		}
		
		$most_recent = FALSE;
		foreach ($auth_array as $auth)
		{
			if (!$most_recent)
				$most_recent = new DateTime($auth['authent_date']);
			else if ($most_recent < new DateTime($auth['authent_date']))
				$most_recent = new DateTime($auth['authent_date']);
		}
		
		if (!$most_recent)
			return "No date";
		$res = $most_recent->format('Y-m-d');
		return $res;
	}
	
	function getCustomerName($id)
	{
		global $tbl_mobileuser;
		
		$res = "";
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			. mysql_real_escape_string($id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($row = mysql_fetch_array($result))
			$res = $row['nom'];
		return $res;
	}
	
	function get_merchant($id)
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($row = mysql_fetch_array($result))
			return $row;
		return FALSE;
	}
	
	function get_customer_list_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification, $customer_array;
		
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
					{
						array_push($id_array, $row_mob['id']);
						//echo("toot");
					}
				else if (!in_array($row_mob['id'], $id_array) && (substr($row_mob['qr_code'], 0, 1) == "2") &&
					($form_values['is_physique'] == 1))	
					{
						array_push($id_array, $row_mob['id']);
						//echo("taat");
					}
			}
		}
		
		return $id_array;
	}
	
	function get_customer_list_super_marchant($form_values, $merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification, $customer_array;
		
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
			
			while ($auth_row = mysql_fetch_array($auth_result))
			{
				$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($auth_row['mobileuser_id'])
					. "'";
				
				//$logger->log('debug', 'stats_calculer_super_marchand', "query::" . $query, Logger::GRAN_MONTH);
				
				$mobi_result = mysql_query($query);
				
				if ($mobi_row = mysql_fetch_array($mobi_result))
				{
					//echo("toto");
					if (!in_array($mobi_row['id'], $id_array) && (substr($mobi_row['qr_code'], 0, 1) == "1") &&
						($form_values['is_app'] == 1))
						array_push($id_array, $mobi_row['id']);
					else if (!in_array($mobi_row['id'], $id_array) && (substr($mobi_row['qr_code'], 0, 1) == "2") &&
						($form_values['is_physique'] == 1))
						array_push($id_array, $mobi_row['id']);
				}
			}
		}

		return $id_array;
	}
	
	function get_customer_list($form_values, $merchant_id) //**
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		doLog("get_customer_list::query::" . $query);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		$id_array = array();
		/// Cas numero 1 : super_marchand
		if ($row = mysql_fetch_array($result))
		{
			doLog("get_customer_list::supermarchant_id::" . $row['supermarchand_id']);
		
			//if ($row['supermarchand_id'] == "-1")
			if ($row['is_supermarchand'] == "1")
				$id_array = get_customer_list_super_marchant($form_values, $merchant_id);
			else
				$id_array = get_customer_list_marchant($form_values, $merchant_id);
		}
		
		return $id_array;
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
