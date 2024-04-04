<?php

	if($_POST['f_key'] != '1gLWc8JEhBs75QujNWRjmkWO3Kro0PeOEoulOwPEtL8o') {
		header('Location: /');
		exit;
	}

	require_once("include/database.class.php");
        require_once("include/session.class.php");
	/// Logs
	require("lib/Logger.class.php");
	$logger = new Logger('./logs/');

	/// DataBase informations
	require_once("dev/service/dbLogInfo.php");
	$tbl_user = "backoffice_usr";
	$tbl_role = "role";
	$tbl_marchand = "marchand";

	/// Role Information
	$role_marchand = "marchands";

	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");

	$login=$_POST['f_login'];
	$mypassword=$_POST['f_pass'];


	/// Gestion checkBox RememberMe
	if(isset($_POST['remember']))
	{
	 	setcookie("cookiemail", $_POST['f_login'], time()+60*60*24*100, "/");
	  	setcookie("cookiepass", $_POST['f_pass'], time()+60*60*24*100, "/");
	}
	else
	{
	  	setcookie("cookiemail","" , NULL, "/");
	  	setcookie("cookiepass","" , NULL, "/");
	}
	///

	$login = stripslashes($login);
	$mypassword = stripslashes($mypassword);
	$login = mysql_real_escape_string($login);
	$mypassword = mysql_real_escape_string($mypassword);

	//////////////////////////////////////////////////////////
	// Attention, il faudra gerer le md5 du password
	//$mypassword = md5($mypassword);

	$sql = "SELECT * FROM $tbl_user WHERE login='$login' and password='$mypassword'";
	$result=mysql_query($sql);
	$count=mysql_num_rows($result);

	/*if($count==1 && $login=='admin')
	{

		$_SESSION['login']=$login;
		//header("location:list_clients.php");
		header("location:test.php");
	}*/
	//else

	if($count == 1)
	{
		$usrRow = mysql_fetch_array($result);

		$sqlRole = "SELECT * FROM $tbl_role WHERE `id` = '"
			. mysql_real_escape_string($usrRow['id_role'])
			. "'";
		$roleResult = mysql_query($sqlRole);

		if (mysql_num_rows($roleResult))
		{
			$roleRow = mysql_fetch_array($roleResult);

			$session = new Session();

			$_SESSION['login'] = $login;
			$_SESSION['usr_id'] = $usrRow['id'];
			$_SESSION['role'] = $roleRow['nom'];

			$logger->log('debug', 'debug_check_php', "Check User: usr_role=" . $_SESSION['role'], Logger::GRAN_MONTH);
			/// Si Marchand, recuperation des infos en session
			if ($_SESSION['role'] == $role_marchand)
			{
				$logger->log('debug', 'debug_check_php', "Check User: in userRoleMarchand", Logger::GRAN_MONTH);

				$sqlMarchand = "SELECT * FROM $tbl_marchand WHERE `id` = '"
					. mysql_real_escape_string($usrRow['id_marchand'])
					. "'";

				$logger->log('debug', 'debug_check_php', "Check User: in userRoleMarchand::marchandQuery=" . $sqlMarchand, Logger::GRAN_MONTH);

				$marchandResult = mysql_query($sqlMarchand);

				if (mysql_num_rows($marchandResult))
				{
					$marchandRow = mysql_fetch_array($marchandResult);

					$_SESSION['logopath'] = $marchandRow['logo'];

					$logger->log('debug', 'debug_check_php', "Check User: in userRoleMarchand::marchand logopath=" . $_SESSION['logopath'], Logger::GRAN_MONTH);
					/// Remplir les infos du marchand
				}
				else
				{
					$login_error = "Error: Problem with the database.";
					require_once("index.php");
				}
			}
			else
			{
				/// Cas d'un bo_user non marchand
				$_SESSION['logopath'] = "static/logos/logoyoufid_hd.png";
			}

			$_SESSION['islogged']='yes';

			/// Redirection vers la page concernee en fonction du type de role
			if ($_SESSION['role'] == "youfid_commerciaux")
			{
				$_SESSION['selector'] = "NEW";
				$_SESSION['selector_merchant_id'] = 0;
				//$_SESSION['selector_current_location'] = "commerciaux_moncompte.php";
				header("location:commerciaux_moncompte.php");
			}
			else if ($_SESSION['role'] == "youfid_master")
			{
				$_SESSION['selector_merchant_id'] = 0;
				$_SESSION['selector'] = "NEW";
				//$_SESSION['selector_current_location'] = "youfid_master_moncompte.php";
				header("location:youfid_master_moncompte.php");
			}
			else if ($_SESSION['role'] == "admin_4g" )
			{
				$_SESSION['selector_merchant_id'] = 0;
				$_SESSION['selector'] = "NEW";
				//$_SESSION['selector_current_location'] = "youfid_master_moncompte.php";
				header("location:youfid_master_moncompte.php");
			}
			else if ($_SESSION['role'] = "marchands"){
				$_SESSION['selector'] = $marchandRow['id'];
				//$_SESSION['selector_current_location'] = "marchand_clients.php";
				header("location:marchand_clients.php");
				//header("location:marchand_moncompte.php");
			}
			else{
				$login_error = "Wrong role";
				require_once 'index.php';
			}

		}
		else
		{
			$login_error = "Error: Problem with the database.";
			require_once("index.php");
		}
	}
	else
	{
	  	$login_error = "Error: Wrong password/login combinaison!";
		require_once("index.php");
	}
?>
