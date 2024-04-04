<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 

	require_once("./lib/Logger.class.php");
	
	if (!isset($logger))
		$logger = new Logger('./logs/');
	
	$logger->log('debug', 'register_new_categorie', "in file", Logger::GRAN_MONTH);

	$error = FALSE;
	$error_msg = "";

	require_once("./dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (!isset($_POST['label_name']) || empty($_POST['label_name']))
		echo "false";
		
	$sqlCheckLabel = "SELECT * FROM label WHERE `nom`='"
		. mysql_real_escape_string($_POST['label_name'])
		. "'";
		
	$result = mysql_query($sqlCheckLabel);
	
	if ($result != FALSE && !mysql_num_rows($result))
	{
		/*$sqlInsertLabel = "INSERT INTO label SET `nom`='"
			. mysql_real_escape_string($_POST['label_name'])
			. "', `type`='"
			. mysql_real_escape_string($_POST['label_type'])
			. "'";*/
			
		$type = "Categorie";
		
		$sqlInsertLabel = "INSERT INTO label SET `nom`='"
			. mysql_real_escape_string($_POST['label_name'])
			. "', `type`='"
			. mysql_real_escape_string($type)
			. "'";
		
		unset($_POST['label_name']);
		//unset($_POST['label_type']);
		
		$logger->log('debug', 'register_new_categorie', "sql_query = " . $sqlInsertLabel, Logger::GRAN_MONTH);
		
		$result = mysql_query($sqlInsertLabel);
			
		if ($result != FALSE)
		{
			$error_msg = "Le label a été rajouté avec succès!";
		}
		else {
			$error = TRUE;
			$error_msg = "Erreur: Probleme avec la base de données.";
		}
	}
	else 
	{
		$error = TRUE;
		$error_msg = "Erreur: Il existe déja un label portant le même nom.";	
	}
	
	$_SESSION['user_error'] = $error_msg;
	
	$logger->log('debug', 'register_new_categorie', "Error_msg=" . $error_msg, Logger::GRAN_MONTH);
	
	if ($error == FALSE)
		echo "true";
	else
		echo "false";
?>
