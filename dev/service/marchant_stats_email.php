#!/usr/bin/php -q
<?php
require_once('utils.php');

proc_nice(2);

define('DB_HOST','db.youfid.fr');
define('DB_PORT','3306');
define('DB_NAME','youfid');
define('DB_USER','youfid');
define('DB_PASS','youfid');
$db = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);


# last 3 days ...

$body_html  = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
$body_html .= "<h2>all merchants activities <small>(last 3 days)</small></h2>";

$q1 = $db->query(" SELECT * FROM `marchand` ORDER BY `supermarchand_id` ");
while($marchand = $q1->fetch(PDO::FETCH_OBJ))
{
	$one_day = 24*3600;
	$date1 = date('Y-m-d', time() - 1 * $one_day);
	$date2 = date('Y-m-d', time() - 2 * $one_day);
	$date3 = date('Y-m-d', time() - 3 * $one_day);

	$res = $db->query("
		SELECT COUNT(`id`) AS n, `marchand_id`
		FROM `transaction`
		WHERE
			`marchand_id` = " . intval($marchand->id)  . " AND
			(
				`transaction_date` LIKE '" . $date1 . " %' OR
				`transaction_date` LIKE '" . $date2 . " %' OR
				`transaction_date` LIKE '" . $date3 . " %'
			)
	")->fetch(PDO::FETCH_OBJ);

	if($res->n == 0 and $marchand->is_active and !$marchand->is_supermarchand and $marchand->demo == 0 and $marchand->label_id != 124) {

		$body_html .= "<p>";
		$body_html .= "(#" . $marchand->id . ") " . $marchand->name . " " . $marchand->phone . " " . $marchand->email_backoffice;
		if(!$res->n) $body_html .= "<b>";
		$body_html .= " >> " . $res->n . " transactions";
		if(!$res->n) $body_html .= "</b>";
		$body_html .= "</p>";

		echo $marchand->id . ' > ' . $res->n . PHP_EOL;

	}

}

$body_html .= "</body></html>";
$from = [ 'email' => 'contact@youfid.fr', 'name' => 'Youfid' ];

foreach ([
	'diallo.salamy@gmail.com',
	'rlaib@youfid.fr',
	'rscuotto@youfid.fr',
] as $to) {
	$to = [ 'email' => $to, 'name' => $to ];
	$subject = 'youfid > all merchants activities (last 3 days)';
	sendgrid_mail($from, $to, $subject, $body_html);
}


# last 7 days ...

$body_html  = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
$body_html .= "<h2>all merchants activities <small>(last 3 days)</small></h2>";

$q1 = $db->query(" SELECT * FROM `marchand` ORDER BY `supermarchand_id` ");
while($marchand = $q1->fetch(PDO::FETCH_OBJ))
{
	$one_day = 24*3600;
	$date1 = date('Y-m-d', time() - 1 * $one_day);
	$date2 = date('Y-m-d', time() - 2 * $one_day);
	$date3 = date('Y-m-d', time() - 3 * $one_day);
	$date3 = date('Y-m-d', time() - 4 * $one_day);
	$date3 = date('Y-m-d', time() - 5 * $one_day);
	$date3 = date('Y-m-d', time() - 6 * $one_day);
	$date3 = date('Y-m-d', time() - 7 * $one_day);

	$res = $db->query("
		SELECT COUNT(`id`) AS n, `marchand_id`
		FROM `transaction`
		WHERE
			`marchand_id` = " . intval($marchand->id)  . " AND
			(
				`transaction_date` LIKE '" . $date1 . " %' OR
				`transaction_date` LIKE '" . $date2 . " %' OR
				`transaction_date` LIKE '" . $date3 . " %' OR
				`transaction_date` LIKE '" . $date4 . " %' OR
				`transaction_date` LIKE '" . $date5 . " %' OR
				`transaction_date` LIKE '" . $date6 . " %' OR
				`transaction_date` LIKE '" . $date7 . " %'
			)
	")->fetch(PDO::FETCH_OBJ);

	if($res->n == 0 and $marchand->is_active and !$marchand->is_supermarchand and $marchand->demo == 0 and $marchand->label_id != 124) {

		$body_html .= "<p>";
		$body_html .= "(#" . $marchand->id . ") " . $marchand->name . " " . $marchand->phone . " " . $marchand->email_backoffice;
		if(!$res->n) $body_html .= "<b>";
		$body_html .= " >> " . $res->n . " transactions";
		if(!$res->n) $body_html .= "</b>";
		$body_html .= "</p>";

		echo $marchand->id . ' > ' . $res->n . PHP_EOL;

	}

}

$body_html .= "</body></html>";


foreach ([
	'diallo.salamy@gmail.com',
	'rlaib@youfid.fr',
	'rscuotto@youfid.fr',
] as $to) {
	$to = [ 'email' => $to, 'name' => $to ];
	$subject = 'youfid > all merchants activities (last 7 days)';
	sendgrid_mail($from, $to, $subject, $body_html);
}



