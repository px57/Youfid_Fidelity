<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
        require_once(dirname(__FILE__) . "/../include/session.class.php");
        $session = new Session();
 
	
	require_once("db_functions.php");
	require_once("loyalty_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	if (!isset($logger))
		$logger = new Logger('../logs/');
	
	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'merchant_push', $message, Logger::GRAN_MONTH);
	}
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$tbl_pushgeoloc = "pushgeoloc";
	
	$tbl_message = "message";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	/// Id Marchand
	$merchant_id = $_SESSION['selector'];
	$last_msg_id = 0;
	
	/// Parametres d'erreur
	$error = FALSE;
	merchant_push($merchant_id);
	echo("La modification a bien été prise en compte!");

	function merchant_push($merchant_id)
	{
		global $tbl_pushgeoloc, $tbl_marchands, $last_msg_id;
		
		$query = "SELECT * FROM $tbl_pushgeoloc WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;
		
		$last_msg_id = create_push_msg($merchant_id);
		
		if ($last_msg_id == FALSE)
			return FALSE;
		
		/// Cas 1 update de la liste de push
		if ($row = mysql_fetch_array($result))
			update_push_list($merchant_id);
		/// Cas 2 creation de la liste de push
		else
			create_push_list($merchant_id);
			
		$is_active = "0";
		if (isset($_POST['is_active']) && !empty($_POST['is_active']))
			$is_active = "1";
			
		$query = "UPDATE $tbl_marchands SET `is_push_actif`='"
			. mysql_real_escape_string($is_active)
			. "' WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		$result = mysql_query($query);
		
		// Envoi mail to admin pour le prevenir qu'il a un nouveau message à valider
		$m_sql = 'SELECT * FROM marchand WHERE id = '.$merchant_id;
		$m_result = mysql_query($m_sql);
		$m_name = "Merchant";
		while($m_row = mysql_fetch_array($m_result))
		{
			$m_name = $m_row['name'];
		}
		
		$m_message = "";
		if (isset($_POST['push_msg']) && !empty($_POST['push_msg']))
		{
			$m_message = $_POST['push_msg'];
		}
		
		$m_admin = 'rlaib@youfid.fr';
		$m_title = 'Nouvelle campagne push-géolocalisée à vérifier';
		$m_url = 'http://backoffice.youfid.fr/youfid_master_validationmes.php';
		$m_body = $m_name.' a soumis une nouvelle campagne de push geolocalisé. <br/>Le message est le suivant : "'.$m_message.'". <br/>Vous pouvez gérer la validation des campagnes push-géolocalisées à l\'adresse suivante: '.$m_url;
		
		mail_youfid($m_admin, $m_title, $m_body);
	}

	function create_push_msg($merchant_id)
	{
		global $tbl_message;
		
		$title = "";
		if (isset($_POST['push_title']) && !empty($_POST['push_title']))
			$title = $_POST['push_title'];
		
		$msg = "";
		if (isset($_POST['push_msg']) && !empty($_POST['push_msg']))
			$msg = $_POST['push_msg'];
		
		$time = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+ 15, date("Y"));
		$date_end = date("Y-m-d", $time);
		$query = "INSERT INTO $tbl_message SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `type`='promo', `points`='0', `message`='"
			. mysql_real_escape_string($title)
			. "', `detail`='"
			. mysql_real_escape_string($msg)
			. "', `start_date`=Now(), `finish_date`='$date_end', `is_validated`='0'";
			
		$result = mysql_query($query);
		
		doLog("INSERT PUSH MSG REQUEST::" . $query);
		
		if ($result == FALSE)
			return FALSE;
		
		/// En cas de succes, on retourn le dernier INSERT id
		return mysql_insert_id();
	}

	function update_push_list($merchant_id)
	{
		global $tbl_marchands;
		
		/// Variables POST
		$is_lundi = "0";
		if (isset($_POST['is_lundi']) && !empty($_POST['is_lundi']))
			$is_lundi = "1";
			
		$is_mardi = "0";
		if (isset($_POST['is_mardi']) && !empty($_POST['is_mardi']))
			$is_mardi = "1";
		
		$is_mercredi = "0";
		if (isset($_POST['is_mercredi']) && !empty($_POST['is_mercredi']))
			$is_mercredi = "1";
		
		$is_jeudi = "0";
		if (isset($_POST['is_jeudi']) && !empty($_POST['is_jeudi']))
			$is_jeudi = "1";
		
		$is_vendredi = "0";
		if (isset($_POST['is_vendredi']) && !empty($_POST['is_vendredi']))
			$is_vendredi = "1";
		
		$is_samedi = "0";
		if (isset($_POST['is_samedi']) && !empty($_POST['is_samedi']))
			$is_samedi = "1";
		
		$is_dimanche = "0";
		if (isset($_POST['is_dimanche']) && !empty($_POST['is_dimanche']))
			$is_dimanche = "1";
			
		$is_active = "0";
		if (isset($_POST['is_active']) && !empty($_POST['is_active']))
			$is_active = "1";
	
		$title = "";
		if (isset($_POST['push_title']) && !empty($_POST['push_title']))
			$title = $_POST['push_title'];
		
		$msg = "";
		if (isset($_POST['push_msg']) && !empty($_POST['push_msg']))
			$msg = $_POST['push_msg'];
		
		$l_start = $_POST['l_start'];
		$l_end = $_POST['l_end'];
		
		$ma_start = $_POST['ma_start'];
		$ma_end = $_POST['ma_end'];
		
		$me_start = $_POST['me_start'];
		$me_end = $_POST['me_end'];
		
		$j_start = $_POST['j_start'];
		$j_end = $_POST['j_end'];
		
		$v_start = $_POST['v_start'];
		$v_end = $_POST['v_end'];
		
		$s_start = $_POST['s_start'];
		$s_end = $_POST['s_end'];
		
		$d_start = $_POST['d_start'];
		$d_end = $_POST['d_end'];
		
		if ($is_lundi == "1")
			update_push($merchant_id, 1, $l_start, $l_end, "1", $title, $msg);
		else
			update_push($merchant_id, 1, $l_start, $l_end, "0", $title, $msg);
		
		if ($is_mardi == "1")
			update_push($merchant_id, 2, $ma_start, $ma_end, "1", $title, $msg);
		else
			update_push($merchant_id, 2, $ma_start, $ma_end, "0", $title, $msg);
		
		if ($is_mercredi == "1")
			update_push($merchant_id, 3, $me_start, $me_end, "1", $title, $msg);
		else
			update_push($merchant_id, 3, $me_start, $me_end, "0", $title, $msg);
		
		if ($is_jeudi == "1")
			update_push($merchant_id, 4, $j_start, $j_end, "1", $title, $msg);
		else
			update_push($merchant_id, 4, $j_start, $j_end, "0", $title, $msg);
		
		if ($is_vendredi == "1")
			update_push($merchant_id, 5, $v_start, $v_end, "1", $title, $msg);
		else
			update_push($merchant_id, 5, $v_start, $v_end, "0", $title, $msg);
		
		if ($is_samedi == "1")
			update_push($merchant_id, 6, $s_start, $s_end, "1", $title, $msg);
		else
			update_push($merchant_id, 6, $s_start, $s_end, "0", $title, $msg);
		
		if ($is_dimanche == "1")
			update_push($merchant_id, 7, $d_start, $d_end, "1", $title, $msg);
		else
			update_push($merchant_id, 7, $d_start, $d_end, "0", $title, $msg);
	}
	
	function update_push($merchant_id, $jour_id, $start, $end, $is_active, $title, $msg)
	{
		global $tbl_pushgeoloc, $last_msg_id;
		
		$query = "UPDATE $tbl_pushgeoloc SET `titre`='"
			. mysql_real_escape_string($title)
			. "', `message`='"
			. mysql_real_escape_string($msg)
			. "', `date_debut`='"
			. mysql_real_escape_string($start)
			. "', `date_fin`='"
			. mysql_real_escape_string($end)
			. "', `is_active`='"
			. mysql_real_escape_string($is_active)
			. "', `msg_last_id`='"
			. mysql_real_escape_string($last_msg_id)
			. "' WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `jour_id`='"
			. mysql_real_escape_string($jour_id)
			. "'";
			
		$result = mysql_query($query);
		return $result;
	}
	
	function create_push_list($merchant_id)
	{
		/// Variables POST
		$is_lundi = "0";
		if (isset($_POST['is_lundi']) && !empty($_POST['is_lundi']))
			$is_lundi = "1";
			
		$is_mardi = "0";
		if (isset($_POST['is_mardi']) && !empty($_POST['is_mardi']))
			$is_mardi = "1";
		
		$is_mercredi = "0";
		if (isset($_POST['is_mercredi']) && !empty($_POST['is_mercredi']))
			$is_mercredi = "1";
		
		$is_jeudi = "0";
		if (isset($_POST['is_jeudi']) && !empty($_POST['is_jeudi']))
			$is_jeudi = "1";
		
		$is_vendredi = "0";
		if (isset($_POST['is_vendredi']) && !empty($_POST['is_vendredi']))
			$is_vendredi = "1";
		
		$is_samedi = "0";
		if (isset($_POST['is_samedi']) && !empty($_POST['is_samedi']))
			$is_samedi = "1";
		
		$is_dimanche = "0";
		if (isset($_POST['is_dimanche']) && !empty($_POST['is_dimanche']))
			$is_dimanche = "1";
			
		$is_active = "0";
		if (isset($_POST['is_active']) && !empty($_POST['is_active']))
			$is_active = "1";
	
		$title = "";
		if (isset($_POST['push_title']) && !empty($_POST['push_title']))
			$title = $_POST['push_title'];
		
		$msg = "";
		if (isset($_POST['push_msg']) && !empty($_POST['push_msg']))
			$msg = $_POST['push_msg'];
		
		$l_start = $_POST['l_start'];
		$l_end = $_POST['l_end'];
		
		$ma_start = $_POST['ma_start'];
		$ma_end = $_POST['ma_end'];
		
		$me_start = $_POST['me_start'];
		$me_end = $_POST['me_end'];
		
		$j_start = $_POST['j_start'];
		$j_end = $_POST['j_end'];
		
		$v_start = $_POST['v_start'];
		$v_end = $_POST['v_end'];
		
		$s_start = $_POST['s_start'];
		$s_end = $_POST['s_end'];
		
		$d_start = $_POST['d_start'];
		$d_end = $_POST['d_end'];
		
		if ($is_lundi == "1")
			create_push($merchant_id, 1, $l_start, $l_end, "1", $title, $msg);
		else
			create_push($merchant_id, 1, $l_start, $l_end, "0", $title, $msg);
		
		if ($is_mardi == "1")
			create_push($merchant_id, 2, $ma_start, $ma_end, "1", $title, $msg);
		else
			create_push($merchant_id, 2, $ma_start, $ma_end, "0", $title, $msg);
		
		if ($is_mercredi == "1")
			create_push($merchant_id, 3, $me_start, $me_end, "1", $title, $msg);
		else
			create_push($merchant_id, 3, $me_start, $me_end, "0", $title, $msg);
		
		if ($is_jeudi == "1")
			create_push($merchant_id, 4, $j_start, $j_end, "1", $title, $msg);
		else
			create_push($merchant_id, 4, $j_start, $j_end, "0", $title, $msg);
		
		if ($is_vendredi == "1")
			create_push($merchant_id, 5, $v_start, $v_end, "1", $title, $msg);
		else
			create_push($merchant_id, 5, $v_start, $v_end, "0", $title, $msg);
		
		if ($is_samedi == "1")
			create_push($merchant_id, 6, $s_start, $s_end, "1", $title, $msg);
		else
			create_push($merchant_id, 6, $s_start, $s_end, "0", $title, $msg);
		
		if ($is_dimanche == "1")
			create_push($merchant_id, 7, $d_start, $d_end, "1", $title, $msg);
		else
			create_push($merchant_id, 7, $d_start, $d_end, "0", $title, $msg);
	}

	function create_push($merchant_id, $jour_id, $start, $end, $is_active, $title, $msg)
	{
		global $tbl_pushgeoloc, $last_msg_id;
		
		$query = "INSERT INTO $tbl_pushgeoloc SET `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "', `jour_id`='"
			. mysql_real_escape_string($jour_id)
			. "', `titre`='"
			. mysql_real_escape_string($title)
			. "', `message`='"
			. mysql_real_escape_string($msg)
			. "', `date_debut`='"
			. mysql_real_escape_string($start)
			. "', `date_fin`='"
			. mysql_real_escape_string($end)
			. "', `is_active`='"
			. mysql_real_escape_string($is_active)
			. "', `msg_last_id`='"
			. mysql_real_escape_string($last_msg_id)
			. "'";
			
		$result = mysql_query($query);
		return $result;
	}
?>
