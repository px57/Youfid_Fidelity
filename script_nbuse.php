<?php

require_once('dev/service/dbLogInfo.php');
mysql_connect("$host", "$username", "$password")or die("cannot connect");
mysql_select_db("$db_name")or die("cannot select DB");

$user_query = 'SELECT * FROM mobileuser';
$user_result = mysql_query($user_query);

$allNoneSupermarchant = "Select * From marchand where is_supermarchand = 0";
$resultNo = mysql_query($allNoneSupermarchant);
$i = 0;
while ($rowMarchand = mysql_fetch_array($resultNo)) {
	$OkMarchand[$i] = $rowMarchand['id'];
	$i += 1;
}

while($user = mysql_fetch_array($user_result))
{
	$sqlGetUser = "SELECT * FROM marchand_has_mobileuser WHERE nb_use > 0 && `mobileuser_id` = '"
	. mysql_real_escape_string($user['id'])
	. "'";
	$nbuse = 0;
	$result2 = mysql_query($sqlGetUser);
	while ($row2 = mysql_fetch_array($result2))
	{
		if (in_array($row2['marchand_id'], $OkMarchand))
			$nbuse += $row2['nb_use'];
	}	
	
	$ins = 'UPDATE mobileuser SET nb_use = "' . $nbuse .'" WHERE id = ' . $user['id'];
//	echo $ins;
	mysql_query($ins);
}				

?>