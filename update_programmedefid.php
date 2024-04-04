<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($_POST['bienvenue'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET offre_bienvenue='" . $_POST['bienvenue'] . "' WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	if (isset($_POST['points'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET points_for_accueil=" . $_POST['points'] . " WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	if (isset($_POST['txt_exp'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET texte_explicatif='" . $_POST['txt_exp'] . "' WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	header("location:../youfid_master_programmedefid.php");
	?>
