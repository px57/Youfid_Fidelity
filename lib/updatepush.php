<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_name = "marchand";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($_POST['every']) && isset($_POST['nonlu'])) {
		$updateMar = "UPDATE $tbl_name SET push_nouvelle_promo='" . $_POST['every'] . "', push_non_lu='" . $_POST['nonlu'] . "'";
		$resultUp = mysql_query($updateMar);
	
	}
	
	header("location:../youfid_master_validationmes.php");
?>
