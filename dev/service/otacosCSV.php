<?php

exit;


proc_nice(2);

$supermarchand_id = 699;
$default_marchand_id = 585; # bordeaux

# should be executed every day at 04:30AM

require_once 'utils.php';
require_once 'dbLogInfo.php';

$db_youfid  = new mysqli($host, $username, $password, "youfid");

/**
	Flux numéro 4 : tout les users modifiés à la journée d'hier
*/

# ...

$csv4_rows[] = [ 'email' => 'email', 'phone' => 'phone', 'first_name' => 'first_name', 'last_name' => 'last_name', 'birthday' => 'birthday', 'updated_at' => 'updated_at', 'status' => 'status', 'points' => 'points', 'optin' => 'optin', ];

$q1 = $db_youfid->query("
	SELECT 	`marchand_has_mobileuser`.`mobileuser_id` AS mobileuser_id
	FROM 	`marchand_has_mobileuser`
	JOIN 	`marchand` ON `marchand`.`id` = `marchand_has_mobileuser`.`marchand_id`
	WHERE 	`marchand`.`supermarchand_id` = " . $supermarchand_id . "
	GROUP BY `marchand_has_mobileuser`.`mobileuser_id`
");

while ($row = $q1->fetch_array(MYSQLI_ASSOC))
{
	$mobileuser = $db_youfid->query("
		SELECT * FROM `mobileuser` WHERE `id` = " . $row['mobileuser_id'] . "
	")->fetch_array(MYSQLI_ASSOC);

	$curl = curl_init('http://api.youfid.fr/dev/service/getMerchantDetails.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('usr_id' => $mobileuser['id'], 'merchant_id' => $default_marchand_id)));
	$response = curl_exec($curl);
	curl_close($curl);
	$points = @json_decode($response)->nb_pts;

	if($points <= 300 and $points <= 0)
		continue;

	$csv4_rows[$mobileuser['id']] = [
		'email' => $mobileuser['mail'],
		'phone' => $mobileuser['phone'],
		'first_name' => $mobileuser['prenom'],
		'last_name' => $mobileuser['nom'],
		'birthday' => $mobileuser['birthdate'],
		'updated_at' => $mobileuser['updated_at'],
		'status' => $mobileuser['fid_status'],
		'points' => $points,
		'optin' => 1 - $mobileuser['unsubscribe'],
	];

}



$filename = '/home/baptiste/Dropbox/share/' . date('Ymd_His') . '_otacos-potential-fraud.csv';

touch($filename);

foreach ($csv4_rows as $row)
	file_put_contents($filename, '"' . implode('";"', $row) . '"' . PHP_EOL, FILE_APPEND);


