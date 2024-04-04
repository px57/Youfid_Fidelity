<?php
// MySQL
try
{
   $youfid = new PDO('mysql:host=db.youfid.fr;dbname=youfid', 'youfid', 'youfid');
}
catch(Exception $error)
{
       die('Error : '.$error->getMessage());
}


	$mobileuser = $youfid->prepare('SELECT * FROM mobileuser');
	$mobileuser->execute();
	$count=0;
	
	while($mobileuser_data = $mobileuser->fetch()) {
				$setvalidation = $youfid->prepare('UPDATE mobileuser SET validation = ? WHERE id = ?');
				$setvalidation->execute(array($mobileuser_data['status'].md5($mobileuser_data['mail']), $mobileuser_data['id']));
				
				$issetvalidation = $setvalidation->rowCount();
				if($issetvalidation == 1) $count++;
			}
	echo $count;
?>
