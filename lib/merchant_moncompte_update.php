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
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	/// Parametres d'erreur
	$error = FALSE;

	/// Variables POST
	$pin_marchand = "0";
	if (isset($_POST['pinmarchand']) && !empty($_POST['pinmarchand']))
		$pin_marchand = "1";
	if (isset($_POST['pin_code']) && !empty($_POST['pin_code']))
		$pin_code = $_POST['pin_code'];
	else
		$pin_code = "";

	$error = merchant_moncompte_update($pin_marchand, $pin_code);
	
	if ($error == TRUE)
		echo("Mise a jour du compte effectuée avec succès!");
	else
		echo("Erreur lors de la mise a jour du compte... Veuillez réessayer plus tard, ou contactez un administrateur si le problème persiste.");
	
	/// Update un compte marchand en bdd
	function merchant_moncompte_update($pin_marchand, $pin_code)
	{
		global $tbl_marchands;
		
		/// Id Marchand
		$merchant_id = $_SESSION['selector'];
		
		$query = "UPDATE $tbl_marchands SET `is_pin_marchand`='"
			. mysql_real_escape_string($pin_marchand)
			. "', `pin_code`='"
			. mysql_real_escape_string($pin_code)
			. "' WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		//echo($query);
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
			
		return TRUE;
	}

?>
