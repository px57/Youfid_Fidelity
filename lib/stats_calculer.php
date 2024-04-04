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
	
	$error_msg = stats_calculer();
	
	echo($error_msg);
	
	/// Dans le js: verifier si date_start < date_end
	function stats_calculer()
	{
		global $logger;
		
		$form_values = check_form();
		
		if (!$form_values)
			return "Erreur avec le formulaire.";
		
		$result = "";
		switch($form_values['filterlist'])
		{
			case 1:
				$result = stats_nb_user($form_values);
				break;
			case 2:
				$result = stats_nb_app($form_values);
				break;
			case 3:
				$result = stats_nb_carte($form_values);
				break;
			case 4:
				$result = stats_nb_email($form_values);
				break;
			case 5:
				$result = stats_nb_cadeaux($form_values);
				break;
			case 6:
				$result = stats_nb_marchand($form_values);
				break;
			case 7:
				$result = stats_nb_scan($form_values); /// TODO
				break;
			case 8:
				$result = stats_nb_geoloc($form_values);
				break;
			case 9:
				$result = stats_nb_notif($form_values);
				break;
			case 10:
				$result = stats_nb_notif_and_geoloc($form_values);
				break;
			case 11:
				$result = stats_nb_promo($form_values); /// TODO
				break;
			case 12:
				$result = stats_nb_push($form_values); /// TODO
				break;
			default:
				$result = "Not available for the moment";
				break;
		}
		
		return $result;
	}
	
	/// nombre de "push géolocalisé" envoyés
	function stats_nb_push($form_values)
	{
		global $logger, $tbl_push;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_push WHERE TO_DAYS(`push_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`push_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " push géolocalisés trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre de scan total
	function stats_nb_scan($form_values)
	{
		global $logger, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_authentification WHERE TO_DAYS(`authent_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " scans trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre de commerçants
	function stats_nb_marchand($form_values)
	{
		global $logger, $tbl_marchands;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_marchands WHERE TO_DAYS(`date_inscription`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_inscription`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_marchands::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
		{
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				if ($row['is_supermarchand'] == "0")
					$count += 1;
			}
			if ($count > 0)
				return strval($count) . " marchands trouvés sur cette période";
		}
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// Check si le type d'un message == 'promo' message_id
	function is_msg_promo($msg_id)
	{
		global $tbl_msg;
		
		$query = "SELECT * FROM $tbl_msg WHERE `id`='"
			. mysql_real_escape_string($msg_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			if ($row['type'] == 'promo')
				return TRUE;
		}
		return FALSE;
	}
	
	/// nombre de promos envoyés
	function stats_nb_promo($form_values)
	{
		global $logger, $tbl_msg_had_mbu, $tbl_msg;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_msg_had_mbu WHERE TO_DAYS(`date_creation`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_creation`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
		{
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				if (is_msg_promo($row['message_id']))
					$count += 1;
			}
			if ($count == 0)
				return ("Aucun résultat trouvé sur cette période");
			return strval(mysql_num_rows($result)) . " promotions trouvés sur cette période";	
		}
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// Nombre de personnes ayant accepté la Géoloc et le push
	function stats_nb_notif_and_geoloc($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`accept_push`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`accept_push`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "') AND TO_DAYS(`accept_geoloc`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`accept_geoloc`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_user::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " utilisateurs trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// Nombre de personnes qui ont acceptées le push
	function stats_nb_notif($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`accept_push`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`accept_push`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_user::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " utilisateurs trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// Nombre de personnes qui ont acceptées la géolocalisation
	function stats_nb_geoloc($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`accept_geoloc`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`accept_geoloc`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_user::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " utilisateurs trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre d'adresses e-mail collectées
	function stats_nb_email($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`date_inscription`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_inscription`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_user::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
			return strval(mysql_num_rows($result)) . " utilisateurs trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre d'utilisateurs de carte physique
	function stats_nb_carte($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`date_inscription`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_inscription`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
			
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_app::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
		{
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				if (substr($row['qr_code'], 0, 1) == "2")
					$count += 1;
			}
			return strval($count) . " utilisateurs trouvés sur cette période";
		}
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre de cadeaux distribués
	function stats_nb_cadeaux($form_values)
	{
		global $tbl_transaction, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_transaction WHERE TO_DAYS(`transaction_date`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`transaction_date`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "') AND `value` < 0";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in function::stats_nb_cadeaux::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
		{
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				if ($row['nb_cadeaux'])
					$count += $row['nb_cadeaux'];
			}
			return strval($count) . " cadeaux trouvés sur cette période";
		}
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre d’utilisateurs de l’application
	function stats_nb_app($form_values)
	{
		global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`date_inscription`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_inscription`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
			
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_app::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";
		
		if (mysql_num_rows($result))
		{
			$count = 0;
			while ($row = mysql_fetch_array($result))
			{
				if (substr($row['qr_code'], 0, 1) == "1")
					$count += 1;
			}
			return strval($count) . " utilisateurs trouvés sur cette période";
		}
		return ("Aucun résultat trouvé sur cette période");
	}
	
	/// nombre d’utilisateurs total
	function stats_nb_user($form_values)
	{
		/*global $tbl_mobileuser, $logger;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE TO_DAYS(`date_inscription`) >= TO_DAYS('"
			. mysql_real_escape_string($date_start)
			. "') AND TO_DAYS(`date_inscription`) <= TO_DAYS('"
			. mysql_real_escape_string($date_end)
			. "')";
		
		if (isset($logger))
			$logger->log('debug', 'stats_calculer', "in file::stats_nb_user::" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Probleme with the db. Please contact admin";*/
			
		global $logger, $tbl_marchands, $tbl_mobileuser, $tbl_authentification;
		
		$date_start = sql_date_format($form_values['date_start']);
		$date_end = sql_date_format($form_values['date_end']);
		
		$query = "SELECT * FROM $tbl_authentification WHERE TO_DAYS(`authent_date`) >= TO_DAYS('"
				. mysql_real_escape_string($date_start)
				. "') AND TO_DAYS(`authent_date`) <= TO_DAYS('"
				. mysql_real_escape_string($date_end)
				. "')";
				
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return "Error with the DDB.";
		
		$id_array = array();		
		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
			
			$result_mobi = mysql_query($query);
			
			if ($row_mob = mysql_fetch_array($result_mobi))
			{
				if (!in_array($row_mob['id'], $id_array))
					array_push($id_array, $row_mob['id']);
			}
		}
		//return count($id_array);
		
		if (count($id_array))
			return strval(count($id_array)) . " utilisateurs trouvés sur cette période";
		return ("Aucun résultat trouvé sur cette période");
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
		
		return $form_values;
	}
?>
