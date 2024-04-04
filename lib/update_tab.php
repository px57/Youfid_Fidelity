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
	
	if (isset($_GET['idcadeau'])) {
		$updateMarchand = "DELETE FROM $tbl_name WHERE id='" . $_GET['idcadeau'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	header("location:../youfid_master_programmedefid.php");
?>
