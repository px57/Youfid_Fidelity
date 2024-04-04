<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_name = "marchand";
	$marchand_id = $_SESSION['selector'];
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$pushactif ='0'; 
	if (isset($_POST['is_push_active'])) {
		$pushactif = '1';
	}
	
	$updateMar = "UPDATE $tbl_name SET is_push_actif='" . $pushactif . "', distance_push='" . $_POST['distance'] . "' WHERE id='" . $marchand_id . "'";
	$resultUp = mysql_query($updateMar);
	
	header("location:../youfid_master_pushgeo.php");
?>
