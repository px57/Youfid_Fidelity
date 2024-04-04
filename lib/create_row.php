<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_name = "cadeau";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($_POST['nom']) && isset($_POST['cout']) && isset($_POST['marchand_id'])) {
		$createCadeau =  "INSERT INTO $tbl_name SET `cout`='"
				 	. mysql_real_escape_string($_POST['cout'])
					. "', `nom`='"
					. mysql_real_escape_string($_POST['nom']) 
					. "', `marchand_id`='" 
					. mysql_real_escape_string($_POST['marchand_id'])
					. "'" ;
		$resultUp = mysql_query($createCadeau);
	}
	
	header("location:../youfid_master_programmedefid.php");
?>
