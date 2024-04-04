<?php
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	require_once 'push_message.php';
	require_once("../dev/service/dbLogInfo.php");
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");

	$today =  date('Y-m-d');
	$getValidMessage = "Select * from message WHERE start_date = '$today'";
	$result = mysql_query($getValidMessage);

	echo($getValidMessage);

	if (mysql_num_rows($result)) 
	{
		//echo("RESULT!!!");
		
		$totalTab = array();
		while ($messageRow = mysql_fetch_array($result)) {
			$rowTab = array();
			$rowTab['id_msg'] = $messageRow['id'];
			$rowTab['id_marchand'] = $messageRow['marchand_id'];
			
			$usersTab = array();
			$getUsers = "SELECT * FROM message_has_mobileuser WHERE message_id = " . $messageRow['id'];
			$resultUser = mysql_query($getUsers);
			while ($userRow = mysql_fetch_array($resultUser)){
				array_push($usersTab, $userRow['mobileuser_id']);
			}
			$rowTab['id_users'] = $usersTab;
			array_push($totalTab, $rowTab);
		}
		
		//print_r($totalTab);
		send_push_msg($totalTab);
	}
?>
