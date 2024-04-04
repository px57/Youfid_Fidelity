<?php

require_once('dev/service/dbLogInfo.php');
mysql_connect("$host", "$username", "$password")or die("cannot connect");
mysql_select_db("$db_name")or die("cannot select DB");

$test = 'SELECT * FROM mobileuser where id > 6000';
$ResultTest = mysql_query($test);
while($row = mysql_fetch_array($ResultTest)){
	$nawak = 'SELECT marchand_id FROM marchand_has_mobileuser WHERE mobileuser_id = ' . $row['id'] . ' ORDER BY nb_use DESC LIMIT 10';
	$ResultNawak = mysql_query($nawak);
	while ($rowNawak = mysql_fetch_array($ResultNawak)) {
		$result3 = mysql_query('Select * From marchand where is_supermarchand = 0 && id = ' . $rowNawak['marchand_id']);
		if (mysql_num_rows($result3)){
			$row3 = mysql_fetch_array($result3);
			$ins = 'UPDATE mobileuser SET first_merchant="' . $row3['name'] .'" WHERE id = ' . $row['id'];
//			echo $ins;
			mysql_query($ins);
			break;
		}
	}	
}

echo "Script achieved";

?>