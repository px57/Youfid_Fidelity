<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	require_once 'push_message.php';
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($_POST['id'])) {
		$messContent = str_replace("'", "\'", $_POST['message_content']);
		$updateCadeau = "UPDATE message SET is_validated='1', detail = '" . $messContent . "' WHERE id='" . $_POST['id'] . "'";
		$resultUp = mysql_query($updateCadeau);
		
		$today =  date('Y-m-d');
		$getMarchandId = "Select * from message WHERE id='" . $_POST['id'] . "'"; 
		// && start_date <= '$today' && finish_date >= '$today'";
		$marchandResult = mysql_query($getMarchandId);
		
		if ($_SESSION['role'] == "youfid_master" && mysql_num_rows($marchandResult)) {
			$rowMarchand = mysql_fetch_array($marchandResult);
			if ($rowMarchand['message'] == "A proximite")
				header("location:../youfid_master_validationmes.php");
			$getUsersId = "Select * from message_has_mobileuser where message_id ='". $_POST['id'] . "'";
			$resultUsers = mysql_query($getUsersId);
			$users = array();
			while ($rowUser = mysql_fetch_array($resultUsers)){
				//do_push_message($rowMarchand['marchand_id'], $rowUser['mobileuser_id'], $_POST['id']);
				array_push($users, $rowUser['mobileuser_id']);
			}
			$rowTab = array();
			$grotab = array();
			$rowTab['id_msg'] = $_POST['id'];
			$rowTab['id_marchand'] = $rowMarchand['marchand_id'];
			$rowTab['id_users'] = $users;
			array_push($grotab, $rowTab);
			//print_r($grotab);
			send_push_msg($grotab);
						
					
		}
		
	}
	header("location:../youfid_master_validationmes.php");

?>
