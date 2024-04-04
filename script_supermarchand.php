<?php

$supermarchand_id = 0;

require_once('dev/service/dbLogInfo.php');
mysql_connect("$host", "$username", "$password")or die("cannot connect");
mysql_select_db("$db_name")or die("cannot select DB");

// MHM

$test = 'SELECT mhm.marchand_id, mhm.mobileuser_id, mhm.nb_use'
	. ' FROM marchand_has_mobileuser mhm JOIN marchand m '
	. ' WHERE m.id = mhm.marchand_id AND m.supermarchand_id = ' . $supermarchand_id;
$ResultTest = mysql_query($test);

echo $test;

while($rowMarchand = mysql_fetch_array($ResultTest))
{
	echo "<br/>In marchand = " . $rowMarchand['marchand_id'] . " and mobileuser = " .$rowMarchand['mobileuser_id'] ;
	
	$verif = 'SELECT * FROM marchand_has_mobileuser WHERE marchand_id = ' . $supermarchand_id 
			. ' AND mobileuser_id = ' .$rowMarchand['mobileuser_id'];	
	$ResultVerif = mysql_query($verif);
	
	echo "</br>".$verif;
	
	$rowSupermarchand = mysql_fetch_array($ResultVerif);
	
	if(!mysql_num_rows($ResultVerif))
	{
		$insert = 'INSERT INTO marchand_has_mobileuser SET marchand_id = ' . $supermarchand_id .', mobileuser_id = ' .$rowMarchand['mobileuser_id']
			. ', nb_use = ' . $rowMarchand['nb_use'] . ', creation_date = NOW()';
		mysql_query($insert);
		
		echo " -> in insert : " .$insert;
	}
	
	else
	{
		echo " -> in update nb_use";
		$newUse = 0;
		
		$test2 = 'SELECT mhm.nb_use'
		. ' FROM marchand_has_mobileuser mhm JOIN marchand m '
		. ' WHERE m.id = mhm.marchand_id AND mhm.mobileuser_id = ' . $rowMarchand['mobileuser_id']
		. ' AND m.supermarchand_id = ' . $supermarchand_id;
		$ResultTest2 = mysql_query($test2);
		
		echo $test2;
		
		while($rowMarchand2 = mysql_fetch_array($ResultTest2))
		{
			$newUse += $rowMarchand2['nb_use'];
		}
		
		$update = 'UPDATE marchand_has_mobileuser SET nb_use = ' . $newUse . ' WHERE marchand_id = ' . $supermarchand_id
		. ' AND mobileuser_id = ' . $rowMarchand['mobileuser_id'];
		mysql_query($update);
		
	}
}

// AUTHENT

$test = 'SELECT a.marchand_id, a.mobileuser_id, a.authent_date'
	. ' FROM authentification a JOIN marchand m '
	. ' WHERE m.id = a.marchand_id AND m.supermarchand_id = ' . $supermarchand_id;
$ResultTest = mysql_query($test);

echo $test;

while($rowMarchand = mysql_fetch_array($ResultTest))
{
	echo "<br/>In marchand = " . $rowMarchand['marchand_id'] . " and mobileuser = " .$rowMarchand['mobileuser_id'] ;
	
	$verif = 'SELECT * FROM authentification WHERE marchand_id = ' . $supermarchand_id 
			. ' AND mobileuser_id = ' .$rowMarchand['mobileuser_id'];	
	$ResultVerif = mysql_query($verif);
	
	echo "</br>".$verif;
	
	$rowSupermarchand = mysql_fetch_array($ResultVerif);
	
	if(!mysql_num_rows($ResultVerif))
	{
		$insert = 'INSERT INTO authentification SET marchand_id = ' . $supermarchand_id .', mobileuser_id = ' .$rowMarchand['mobileuser_id']
			. ", authent_date = '" . $rowMarchand['authent_date'] . "'";
		mysql_query($insert);
		
		echo " -> in insert : " .$insert;
	}
	
	else echo "-> out";
}


?>