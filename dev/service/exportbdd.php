<?php

require_once('utils.php');

// .....

proc_nice(3);

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

$login    = $argv[1];
$email_to = $argv[2];
$unsubscribe = $argv[3];

if(!$login) exit;
if(!$email_to) exit;

$file_name = 'export-' . md5(uniqid()) . '.csv';
$file_path = __DIR__ . '/exports/' . $file_name;
$file_url  = 'http://api.youfid.fr/dev/service/exports/' . $file_name;

$from = [
  'email' => 'contact@youfid.fr',
  'name' => 'YouFID'
];

sendgrid_mail($from, $email_to, 'youfid > Export Users', "<p>L'export de votre base de données " . $login . " est en cours, veuillez patienter.</p>");

$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

$checkMarchand = $database->prepare("
	SELECT *
	FROM backoffice_usr
	WHERE
		login = :login
	LIMIT 1
");
$checkMarchand->execute([
	':login' => $login,
]);
$backoffice_usr = $checkMarchand->fetch();

$sql = "SELECT mobileuser.*
	FROM mobileuser
	INNER JOIN marchand_has_mobileuser ON marchand_has_mobileuser.mobileuser_id = mobileuser.id
	WHERE ";

if($unsubscribe == '1')
	$sql = $sql . "mobileuser.unsubscribe != 1 AND ";

$sql = $sql . "marchand_has_mobileuser.marchand_id IN (
			SELECT id
			FROM marchand
			WHERE supermarchand_id = :supermarchandId
		) GROUP BY mobileuser.id";

$userDb = $database->prepare($sql);
$userDb->execute([
	'supermarchandId' => $backoffice_usr['id_marchand'],
]);

$csv = fopen($file_path, "wb");
fputcsv($csv, [
	'id',
	'mail',
	'nom',
	'prenom',
	'qr_code',
	'date_inscription',
	'birthdate',
	'phone',
	'address',
	'zip',
	'city',
	'status',
	'fid_status',
	'first_merchant',
	'nb_use',
	'nb_pts',
#	'nb_pts_processed',
#	'nb_pts_burned',
]); //  #31#sinequanone$


$access_token = 'ae0305a9427a91f6f63e55af0eaa1d9c4c02af07f672d15e4a77d99b65327822';

$sleeper = 0;
//ajout des données users dans le csv
while($user = $userDb->fetch())
{

	$api_user = file_get_contents("http://api.youfid.fr/v2/shops/" . $backoffice_usr['id_marchand'] . "/users/" . $user['id'] . "?access_token=" . $access_token);
#	$api_tickets = file_get_contents("http://api.youfid.fr/v2/shops/" . $backoffice_usr['id_marchand'] . "/users/" . $user['id'] . "/tickets?access_token=" . $access_token);
#	$api_coupons = file_get_contents("http://api.youfid.fr/v2/shops/" . $backoffice_usr['id_marchand'] . "/users/" . $user['id'] . "/burned?access_token=" . $access_token);

	fputcsv($csv, [
		$user['id'],
		$user['mail'],
		$user['nom'],
		$user['prenom'],
		$user['qr_code'],
		$user['date_inscription'],
		$user['birthdate'],
		$user['phone'],
		$user['address'],
		$user['zip'],
		$user['city'],
		$user['status'],
		$user['fid_status'],
		$user['first_merchant'],
		$user['nb_use'],
		@json_decode($api_user)->user->n_points,
#		@json_decode($api_tickets)->n_points_processed,
#		@json_decode($api_coupons)->n_points_burned,
	]);

	$sleeper++;

	if($sleeper == 2000) {
		$sleeper = 0;
		sleep(1);
	}

}

$csv = fclose($csv);


sendgrid_mail($from, $email_to, 'youfid > Export Users', "<p>L'export de votre base de données est terminé.<br> Il est disponible en téléchargement durant 48h via ce lien <a href='" . $file_url . "'>" . $file_url  . "</a>.</p>");

exit;
