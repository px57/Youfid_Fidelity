<?php
require_once('utils.php');

// .....

proc_nice(3);

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

$login    = 'sinequanone';
//$email_to = 'charlotte.devauchelle@sinequanone.fr';
$email_to = 'angelina.monteiro@sinequanone.fr';
$to = [
  'email' => $email_to,
  'name' => 'Angelina Monteiro'
];

if(!$login) exit;
if(!$email_to) exit;

# export "classique" des users ...
shell_exec('php ' . __DIR__ . '/exportbdd.php ' . $login . ' ' . $email_to . ' > /dev/null 2>&1 &');

$file_name = 'export-' . md5(uniqid()) . '.csv';
$file_path = __DIR__ . '/exports/' . $file_name;
$file_url  = 'http://api.youfid.fr/dev/service/exports/' . $file_name;

$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

$csv = fopen($file_path, "wb");

fputcsv($csv, [
	'ticket_id',
	'created_at',
	'updated_at',
	'amount',
	'mobileuser_id',
	'merchant_id',
	'status',
]);

$ticketsDB = $database->query("
	SELECT *
	FROM  `sinequanone_tickets`
");

while($ticket = $ticketsDB->fetch())

	fputcsv($csv, [
		$ticket['ticket_id'],
		$ticket['created_at'],
		$ticket['updated_at'],
		$ticket['amount'],
		$ticket['mobileuser_id'],
		$ticket['merchant_id'],
		$ticket['status'],
	]);

$csv = fclose($csv);

$from = [ 'email' => 'contact@youfid.fr', 'name' => 'Youfid' ];
$subject = 'youfid > Export Tickets';
$message = "<p>L'export de votre base de données de tickets est disponible en téléchargement durant 48h via ce lien <a href='" . $file_url . "'>". $file_url . "</a>.</p>";
sendgrid_mail($from, $to, $subject, $message);

exit;
