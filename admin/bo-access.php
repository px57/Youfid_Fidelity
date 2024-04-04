<?php

mysql_connect("db.youfid.fr", "youfid", "youfid")or die("cannot connect");
mysql_select_db("youfid")or die("cannot select DB");
$sqlGetBO = "SELECT * FROM backoffice_usr ORDER BY login ASC";
$result = mysql_query($sqlGetBO);

echo '<img src="http://www.youfid.fr/Content/logo.png"> </br> </br>';
 
echo "<table border='1'>"; 

	echo "<tr>"; 
        echo "<td><b>Login</b></td>"; 
        echo "<td><b>Password</b></td>"; 
    echo "</tr>"; 
 
	while($row = mysql_fetch_array($result))
	{   
		if($row['id_role'] == 4){    
		    echo "<tr>"; 
		        echo "<td>".$row['login']."</td>"; 
		        echo "<td>".$row['password']."</td>"; 
		    echo "</tr>"; 
		}
	} 
 
echo "</table> "; 
?>
