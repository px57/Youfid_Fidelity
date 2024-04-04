<?php
require_once('utils.php');

$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

$sleeper = 0;
$file = file('export-7164094c4eee94bb17074b5467f97714.csv');

foreach ($file as $line)
{

    $csv_data = str_getcsv($line);
    $usr_id = $csv_data[0];

    if(!is_numeric($usr_id))
    	continue;

	$statement = $database->prepare(" SELECT * FROM `temp_otacos50pts` WHERE `usr_id` = :usr_id ");
	$statement->execute(['usr_id' => $usr_id]);
	$is_done = $statement->fetch(PDO::FETCH_OBJ);

	if($is_done)
    	continue;

	$curl = curl_init('http://api.youfid.fr/dev/service/marchandCheckScanUser.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
		'usr_id' => $usr_id,
		'merchant_id' => 699,
		'forced_amount' => 50,
	)));
	$json_response = curl_exec($curl);
	curl_close($curl);

	$database->prepare(" INSERT INTO `temp_otacos50pts` (`usr_id`, `created_at`, `json_response`) VALUES (:usr_id, NOW(), :json_response) ")
	         ->execute([
	         	'usr_id' => $usr_id,
	         	'json_response' => $json_response,
	         ]);


	if($sleeper == 50) {
		$sleeper = 0;
		sleep(1);
	}

}

$from = [ 'email' => 'contact@youfid.fr', 'name' => 'Youfid' ];
$to = [ 'email' => 'diallo.salamy@gmail.com', 'name' => 'Abdoul DIALLO' ];
$subject = 'youfid > Add Points';
$message = 'Add points done.';
sendgrid_mail($from, $to, $subject, $message);
