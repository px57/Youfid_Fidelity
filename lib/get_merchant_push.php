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
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$tbl_pushgeoloc = "pushgeoloc";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	/// Id Marchand
	$merchant_id = $_SESSION['selector'];
	
	/// Parametres d'erreur
	$error = FALSE;
	
	$res = get_merchant_push($merchant_id);
	echo ($res);
	
	function get_merchant_push($merchant_id)
	{
		global $tbl_pushgeoloc;
		
		$query = "SELECT * FROM $tbl_pushgeoloc WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		$push_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$push = array();
			$push['jour_id'] = $row['jour_id'];
			$push['titre'] = $row['titre'];
			$push['message'] = $row['message'];
			$push['date_debut'] = $row['date_debut'];
			$push['date_fin'] = $row['date_fin'];
			$push['is_active'] = $row['is_active'];
			
			array_push($push_array, $push);
		}
		return json_encode($push_array);
	}
?>
