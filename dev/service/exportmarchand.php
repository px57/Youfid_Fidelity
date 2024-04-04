<?php

require_once('utils.php');
$from = [
  'email' => 'contact@youfid.fr',
  'name' => 'YouFID'
];

proc_nice(3);

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

$email_to = $argv[1];
if(!$email_to) exit;

$to = [
	'email' => $email_to,
	'name' => $email_to
];
$file_name = 'export-' . md5(uniqid()) . '.csv';
$file_path = __DIR__ . '/exports/' . $file_name;
$file_url  = 'http://api.youfid.fr/dev/service/exports/' . $file_name;
$subject = 'youfid > Export Marchands';
$message = "<p>L'export des marchands est en cours, veuillez patienter.</p>";

sendgrid_mail($from, $to, $subject, $message);

$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

$marchands = $database->prepare("SELECT * FROM marchand");
$marchands->execute();

$csv = fopen($file_path, "wb");
fputcsv($csv, [
	'id',
	'supermarchand_id',
	'enseigne',
	'entité social',
	'adresse',
	'ville',
	'code postal',
	'téléphone',
	'email',
	'contact',
]);


$sleeper = 0;
while($marchand = $marchands->fetch())
{
	fputcsv($csv, [
		$marchand['id'],
		$marchand['supermarchand_id'],
		$marchand['name'],
		$marchand['company'],
		$marchand['address'],
		$marchand['city'],
		$marchand['zip_code'],
		$marchand['phone'],
		$marchand['email_backoffice'],
		$marchand['contact'],
	]);

	$sleeper++;

	if($sleeper == 20) {
		$sleeper = 0;
		sleep(1);
	}

}

$csv = fclose($csv);
$message = "<p>L'export des marchands est terminé.<br> Il est disponible en téléchargement durant 48h via ce lien <a href='" . $file_url . "'>" . $file_url  . "</a>.</p>";
sendgrid_mail($from, $to, $subject, $message);

exit;
