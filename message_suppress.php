<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 
	
	/// Redirection vers la page index.php si non log
	if (!isset($_SESSION['login']))
		header("location:index.php");
	
	$_SESSION['selector_current_location'] = "youfid_master_validationmes.php";
	require_once("header.php");
	
	$sqlGetMes = "UPDATE message SET is_validated = 2 WHERE id = '"
		. mysql_real_escape_string($_GET['id'])
		. "'";
	$result = mysql_query($sqlGetMes);
	
	if($result!=FALSE)
	{
		echo "Message supprimé </br>";
		echo "<a href='youfid_master_validationmes.php'> Retour </a>";
	}
	//header("location:youfid_master_validationmes.php");
	else
	{
		echo "Erreur lors de la suppression </br>";
		echo "<a href='youfid_master_validationmes.php'> Retour </a>";
	}

	require_once("footer.php"); 
?>
