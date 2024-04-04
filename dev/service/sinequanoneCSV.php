<?php

proc_nice(2);

$supermarchand_id = 1567;
$sinequanone_default_marchand_id = 889751;
$sinequanone_internet_marchand_id = 1685;

$access_token = 'ae0305a9427a91f6f63e55af0eaa1d9c4c02af07f672d15e4a77d99b65327822';

$time = time();

# should be executed every day at 04:30AM

require_once 'utils.php';
require_once 'dbLogInfo.php';

$db_youfid  = new mysqli($host, $username, $password, "youfid");

$ftp = ftp_connect('ftp-in.idcontact.net');
ftp_login($ftp, 'youfid', '26!TYbmf1z');

$activity_log[] = date('Y-m-d');

/**
	Flux numéro 3 : récupération des tickets chez IDContact
*/

# ...
echo 'Importation du CSV idcontact (en local)' . PHP_EOL;
# ...

if(is_file(__DIR__ . '/flux3.csv'))
	unlink(__DIR__ . '/flux3.csv');

$today = date('Ymd');
$log_flux3_files = 0;

foreach (ftp_nlist($ftp, "/OUT") as $remote_csv)
	if(strpos($remote_csv, '_' . $today . '_') and substr($remote_csv, -4) == '.csv')
	{
		$log_flux3_files ++;
		ftp_get($ftp, __DIR__ . '/flux3-temp.csv', $remote_csv, FTP_BINARY);
		file_put_contents(__DIR__ . '/flux3.csv', file_get_contents(__DIR__ . '/flux3-temp.csv'), FILE_APPEND);
		unlink(__DIR__ . '/flux3-temp.csv');
	}

$activity_log[] = 'flux3_files: ' . $log_flux3_files;

$csv3 = file_get_contents(__DIR__ . '/flux3.csv');

# ...
echo 'Injection dans la table youfid.sinequanone_tickets' . PHP_EOL;
# ...

$log_flux3_lines = 0;
$log_flux3_lines_email = 0;

foreach(preg_split("/((\r?\n)|(\r\n?))/", $csv3) as $line)
{

	$data = explode(';', $line);
	foreach ($data as $key => $value)
		if(substr($value, 0, 1) == '"')
			$data[$key] = trim(strtolower(str_replace('"', '', $value)));

	// id_panier;Email du contact;total_base_ttc;date_achat;code_coupon
	$ticket_id   = @$data[0];
	$email       = @$data[1];
	$amount      = intval(@$data[2]);
	$date        = @$data[3];
	$code_coupon = @$data[4];


	if($amount == 0 or empty($ticket_id))
		continue;

	$log_flux3_lines ++;

	if(strpos($ticket_id, 'SN') !== false) {
		$sinequanone_default_marchand_id = $sinequanone_internet_marchand_id;
	}

	//$ticket_id = date('Ymd', time() - 24*3600) . '00' . $ticket_id;

	$db_youfid->query("
		INSERT INTO `sinequanone_tickets` ( `ticket_id` , `created_at`, `merchant_id` ) VALUES ( '" . $ticket_id . "', '" . $date . "', '" . $sinequanone_default_marchand_id . "' )
	");

	$amount = $amount * 100;
	$db_youfid->query("
		UPDATE `sinequanone_tickets` SET `amount` = " . intval($amount) . " WHERE `ticket_id` = '" . $ticket_id . "'
	");

	if(filter_var($email, FILTER_VALIDATE_EMAIL))
	{
		$log_flux3_lines_email ++;

		$mobileuser = $db_youfid->query("
			SELECT * FROM `mobileuser` WHERE `mail` LIKE '" . $email . "' LIMIT 1
		")->fetch_array(MYSQLI_ASSOC);
		$db_youfid->query("
			UPDATE `sinequanone_tickets` SET `mobileuser_id` = " . $mobileuser['id'] . " WHERE `ticket_id` = '" . $ticket_id . "'
		");
	}

}

$activity_log[] = 'flux3_lines: ' . $log_flux3_lines;
$activity_log[] = 'flux3_emails: ' . $log_flux3_lines_email;

unlink(__DIR__ . '/flux3.csv');

/**
	Traitements internes
*/


# ...
echo 'Ajout des points dans YouFID' . PHP_EOL;
# ...


$q1 = $db_youfid->query("
	SELECT 	*
	FROM 	`sinequanone_tickets`
	WHERE
		`mobileuser_id` != 0 AND
		`merchant_id` != 0 AND
		`amount` != 0 AND
		`status` = 'not_processed'
");

$log_tickets_processed = 0;
$log_points_added = 0;

while ($ticket = $q1->fetch_array(MYSQLI_ASSOC))
{
	$log_tickets_processed ++;

	$mobileuser = $db_youfid->query("
		SELECT * FROM `mobileuser` WHERE `id` = " . $ticket['mobileuser_id'] . "
	")->fetch_array(MYSQLI_ASSOC);

	$url = "http://api.youfid.fr/dev/service/marchandCheckScanUser.php";
	$content = json_encode([
		'usr_id' => $mobileuser['id'],
		'merchant_id' => $ticket['merchant_id'],
		'qr_code' => $mobileuser['qr_code'],
		'forced_amount' => floor($ticket['amount'] / 100),
	]);

	$log_points_added += floor($ticket['amount'] / 100);


	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

	$json_response = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	echo $ticket['ticket_id'] . PHP_EOL;
	echo $json_response . PHP_EOL;

/*
	# temp

	// http://docs.sendgrid.com/documentation/get-started/integrate/examples/php-example-using-the-web-api/
	$sendgrid_api_fields = [
		'api_user' => urlencode('5inq'),
		'api_key' => urlencode('nh31oCgNtAxQkQnJY3rqjdU3'),
		'from' => urlencode('sav@youfid.fr'), 'fromname' => urlencode('sav@youfid.fr'),
		'to'   => urlencode('bonjour+youfid@5inq.fr'), 'toname' => urlencode('bonjour+youfid@5inq.fr'),
		'subject' => urlencode('youfid > sinequanone json_response'),
		'html' => urlencode('json_response = ' . $json_response),
		'x-smtpapi' => json_encode(['category' => ['youfid', 'youfid_ipblocked']]),
	];

	$postfields = '';
	foreach($sendgrid_api_fields as $key=>$value)
	  $postfields .= $key.'='.$value.'&';
	rtrim($postfields, '&');

	ob_start();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://sendgrid.com/api/mail.send.json');
	curl_setopt($ch, CURLOPT_POST, count($sendgrid_api_fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($ch);
	curl_close($ch);
	ob_end_clean();

	# /temp
*/

	$db_youfid->query("
		UPDATE `mobileuser` SET `updated_at` = NOW() WHERE `id` = " . $mobileuser['id'] . "
	");

	$db_youfid->query("
		UPDATE `sinequanone_tickets`
		SET
			`status` = 'processed',
			`json_response` = '" . $db_youfid->real_escape_string($json_response) . "'
		WHERE `ticket_id` = '" . $ticket['ticket_id'] . "'
	");

}

$activity_log[] = 'tickets_processed: ' . $log_tickets_processed;
$activity_log[] = 'points_added: ' . $log_points_added;

$log_users = 0;
$log_points_18_months = 0;

# ...
echo 'Recalcul des youfid.mobileuser.fid_status' . PHP_EOL;
# ...

$previous_fid_status = [];

$q1 = $db_youfid->query("
	SELECT 	`marchand_has_mobileuser`.`mobileuser_id` AS mobileuser_id
	FROM 	`marchand_has_mobileuser`
	JOIN 	`marchand` ON `marchand`.id = `marchand_has_mobileuser`.marchand_id
	WHERE 	`marchand`.`supermarchand_id` = " . $supermarchand_id . "
	GROUP BY `marchand_has_mobileuser`.`mobileuser_id`
");

while ($row = $q1->fetch_array(MYSQLI_ASSOC))
{

	# auto-correct ...
	// file_get_contents('http://api.youfid.fr/v2/shops/1585/users/' . $row['mobileuser_id'] . '/tickets?access_token=' . $access_token);

	$log_users ++;

	$mobileuser = $db_youfid->query("
		SELECT * FROM `mobileuser` WHERE `id` = " . $row['mobileuser_id'] . "
	")->fetch_array(MYSQLI_ASSOC);

	if(!$mobileuser)
		continue;

	$previous_fid_status[$mobileuser['id']] = $mobileuser['fid_status'];

	$q3 = $db_youfid->query("
		SELECT SUM( amount ) AS total_amount
		FROM `sinequanone_tickets`
		WHERE 	`mobileuser_id` = " . $mobileuser['id'] . " AND
				`created_at` > '" . date('Y-m-d H:i:s', $time - (18 * 30 * 24 * 3600)) . "'
	");
	$total_amount = @$q3->fetch_array(MYSQLI_ASSOC)['total_amount'];

	$log_points_18_months = floor($total_amount / 100);

	/*
	0000 > Nacre : Entre 0 et 200€
		15€ offerts le jour de mon anniversaire

	1000 > Argent : Entre 200 et 400€
		15€ offerts le jour de mon anniversaire
		Ventes Privées en avant-première
		-15% sur mon article préféré (1 fois par saison)

	2000 > Or : Entre 400 et 600€
		30€ offerts le jour de mon anniversaire
		Ventes Privées en avant-première
		-15% sur mon article préféré (1 fois par saison)
		Retouches simples offertes

	3000 > Diamant : + de 600€
		30€ offerts le jour de mon anniversaire
		Ventes Privées en avant-première
		-15% sur mon article préféré (1 fois par saison)
		Retouches simples offertes
		Présentation privée des collections en avant première et précommande possibles + un cadeau
	*/
	$fid_status_a = 0;
	if($total_amount >= 20000) $fid_status_a = 1;
	if($total_amount >= 40000) $fid_status_a = 2;
	if($total_amount >= 60000) $fid_status_a = 3;

	$fid_status_b = @$mobileuser['fid_status'][3];
	if(!$fid_status_b or date('m-d') == '02-01' or date('m-d') == '08-01') # à chaque 1er fev ou 1er aout, on remonte les status
		$fid_status_b = 1;
	if($fid_status_a <= 1)
		$fid_status_b = 0;

	$fid_status = $fid_status_a . "00" . $fid_status_b;

	if($fid_status == $mobileuser['fid_status'])
		continue;

	$db_youfid->query("
		UPDATE `mobileuser` SET `fid_status` = '" . $fid_status . "' WHERE `id` = " . $mobileuser['id'] . "
	");

}


/**
	Flux numéro 4 : tout les users modifiés à la journée d'hier
	Flux numéro 6, coupons des users modifiés hier
*/

# ...
echo 'Flux4 USER, création' . PHP_EOL;
echo 'Flux6 COUPONS, création' . PHP_EOL;
# ...

$mobileusers_modified = [];
$csv_users_rows[] = [ 'email' => 'email', 'phone' => 'phone', 'first_name' => 'first_name', 'last_name' => 'last_name', 'birthday' => 'birthday', 'updated_at' => 'updated_at', 'previous_status' => 'previous_status', 'status' => 'status', 'points' => 'points', 'optin' => 'optin', 'n_coupons' => 'n_coupons', ];
$csv_coupons_rows[] = [ 'email' => 'email', 'coupon_id' => 'coupon_id',  'coupon_name' => 'coupon_name',  'coupon_code' => 'coupon_code',  'coupon_n_points' => 'coupon_n_points', ];


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

	$updated_from = $time - 22*3600;
	if(strtotime($mobileuser['updated_at']) < $updated_from)
		continue;


	# Flux COUPONS ...

	$n_coupons = 0;
	$api_coupons = json_decode(file_get_contents('http://api.youfid.fr/v2/shops/1585/users/' . $mobileuser['id'] . '/coupons?access_token=' . $access_token));

	foreach ($api_coupons->coupons as $coupon)
	{
		$csv_coupons_rows[] = [
			'email' => $mobileuser['mail'],
			'coupon_id' => $coupon->id,
			'coupon_name' => $coupon->name,
			'coupon_code' => $coupon->code,
			'coupon_n_points' => $coupon->n_points,
		];
		$n_coupons ++;
	}


	# Flux USERS ...

	$curl = curl_init('http://api.youfid.fr/dev/service/getMerchantDetails.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('usr_id' => $mobileuser['id'], 'merchant_id' => 1585)));
	$response = curl_exec($curl);
	curl_close($curl);
	$points = @json_decode($response)->nb_pts;

	$mobileusers_modified[$mobileuser['id']] = $mobileuser['mail'];

	$csv_users_rows[$mobileuser['id']] = [
		'email' => $mobileuser['mail'],
		'phone' => $mobileuser['phone'],
		'first_name' => $mobileuser['prenom'],
		'last_name' => $mobileuser['nom'],
		'birthday' => $mobileuser['birthdate'],
		'updated_at' => $mobileuser['updated_at'],
		'previous_status' => $previous_fid_status[$mobileuser['id']],
		'status' => $mobileuser['fid_status'],
		'points' => $points,
		'optin' => 1 - $mobileuser['unsubscribe'],
		'n_coupons' => $n_coupons,
	];

	echo '.';
}
echo PHP_EOL;

if(is_file(__DIR__ . '/flux4.csv')) unlink(__DIR__ . '/flux4.csv');
touch(__DIR__ . '/flux4.csv');
foreach ($csv_users_rows as $row)
	file_put_contents(__DIR__ . '/flux4.csv', '"' . implode('";"', $row) . '"' . PHP_EOL, FILE_APPEND);


if(is_file(__DIR__ . '/flux6.csv')) unlink(__DIR__ . '/flux6.csv');
touch(__DIR__ . '/flux6.csv');
foreach ($csv_coupons_rows as $row)
	file_put_contents(__DIR__ . '/flux6.csv', '"' . implode('";"', $row) . '"' . PHP_EOL, FILE_APPEND);


/**
	Flux numéro 5, tickets consolidé d'hier
*/

# ...
echo 'Flux5 TICKETS, création' . PHP_EOL;
# ...

$csv_tickets_rows[] = [ 'Numero_Ticket' => 'Numero_Ticket', 'email' => 'email', ];

$q1 = $db_youfid->query("
	SELECT 	*
	FROM 	`sinequanone_tickets`
	WHERE 	`updated_at` LIKE '" . date('Y-m-d', time()) . "%' AND
			`mobileuser_id` > 0 AND
			`amount` > 0
");

while ($ticket = $q1->fetch_array(MYSQLI_ASSOC))
{
	$mobileuser = $db_youfid->query("
		SELECT * FROM `mobileuser` WHERE `id` = " . $ticket['mobileuser_id'] . "
	")->fetch_array(MYSQLI_ASSOC);

	# because tickets starts by 2016091400...
	//$ticket_id = substr($ticket['ticket_id'], 10);
	$ticket_id = $ticket['ticket_id'];

	$csv_tickets_rows[$mobileuser['id']] = [
		'Numero_Ticket' => $ticket_id,
		'email' => $mobileuser['mail'],
	];

}

if(is_file(__DIR__ . '/flux5.csv')) unlink(__DIR__ . '/flux5.csv');
touch(__DIR__ . '/flux5.csv');
foreach ($csv_tickets_rows as $row)
	file_put_contents(__DIR__ . '/flux5.csv', '"' . implode('";"', $row) . '"' . PHP_EOL, FILE_APPEND);




# ...
echo 'Flux4 USER, upload' . PHP_EOL;
# ...

ftp_put($ftp, '/IN/THEOZ_17_YOUFID_FID_SN_' . date('Ymd_His') . '.csv', __DIR__ . '/flux4.csv', FTP_ASCII);
sleep(60);

unlink(__DIR__ . '/flux4.csv');

$activity_log[] = 'flux4_users: ' . count($csv_users_rows);


# ...
echo 'Flux5 TICKETS, upload' . PHP_EOL;
# ...

ftp_put($ftp, '/IN/THEOZ_17_YOUFID_TICKET_SN_' . date('Ymd_His') . '.csv', __DIR__ . '/flux5.csv', FTP_ASCII);
sleep(60);

unlink(__DIR__ . '/flux5.csv');

$activity_log[] = 'flux5_tickets: ' . count($csv_tickets_rows);


# ...
echo 'Flux6 COUPONS, upload' . PHP_EOL;
# ...

ftp_put($ftp, '/IN/THEOZ_17_YOUFID_COUPON_SN_' . date('Ymd_His') . '.csv', __DIR__ . '/flux6.csv', FTP_ASCII);
sleep(60);

unlink(__DIR__ . '/flux6.csv');

$activity_log[] = 'flux6_coupons: ' . count($csv_coupons_rows);



# ...
echo 'End.' . PHP_EOL;
# ...

$activity_log_lines = array_reverse(file(__DIR__ . '/sinequanone-activity.log', FILE_IGNORE_NEW_LINES));
$activity_log_lines[] = implode(' | ', $activity_log);

unlink(__DIR__ . '/sinequanone-activity.log');
file_put_contents(__DIR__ . '/sinequanone-activity.log', implode(PHP_EOL, array_reverse($activity_log_lines)));
