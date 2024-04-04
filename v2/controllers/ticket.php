<?php

#
# get
#

$app->get('/shops/{shop_id}/users/{user_id}/tickets', function($shop_id, $user_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/users/' . $user_id . '?access_token=' . $_REQUEST['access_token'];
	$api_user = json_decode(file_get_contents($api_url), true);
	if($api_user['status']['code'] != 200)
		return $app->json($api_user);

	$q1 = $app->db->query("
		SELECT 	*
		FROM 	`sinequanone_tickets`
		WHERE 	`mobileuser_id` = " . intval($user_id) . "
		ORDER BY `created_at` DESC
	");

	$tickets = [];
	while ($ticket = $q1->fetch_array(MYSQLI_ASSOC))
		$tickets[] = $ticket;

	foreach ($tickets as $key => $ticket)
		$tickets[$key]['marchand'] = $app->db->query("
								SELECT *
								FROM `marchand`
								WHERE `id` = " . intval($ticket['merchant_id']) . "
						    ")->fetch_assoc();

	# ...
	# hack de rattrappage du nombre de points
	$n_points_counted = 0;

	// point gagnés sur les tickets
	foreach ($tickets as $t)
		if($t['status'] == 'processed')
			$n_points_counted += $t['amount'] / 100;

	$n_points_processed = $n_points_counted;

	// point dépensés sur les coupons burned
	$api_burned = json_decode(file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/users/' . $user_id . '/burned?access_token=' . $_REQUEST['access_token']), true);
	if($api_burned['status']['code'] == 200)
		$n_points_counted -= $api_burned['n_points_burned'];

	// il manque des points dans YF ?
	//if($n_points_counted > $api_user['user']['n_points'])
	//{
		$curl = curl_init("http://api.youfid.fr/dev/service/marchandCheckScanUser.php");
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
			'usr_id' => $api_user['user']['id'],
			'merchant_id' => $shop_id,
			'qr_code' => $api_user['user']['qr_code'],
			'forced_amount' => floor($n_points_counted - $api_user['user']['n_points']),
		]));
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
	//}

/*
	// il y a des points en trop dans YF ?
	if($n_points_counted < $api_user['user']['n_points'])
	{
		$amount = ($n_points_counted - $api_user['user']['n_points']) * 100;
		$app->db->query("
			INSERT INTO  `sinequanone_tickets` (
				`ticket_id`, `created_at`, `updated_at`, `amount`, `mobileuser_id`, `merchant_id`, `status`
			) VALUES (
				'SAV-" . uniqid() . "', NOW(), NOW(), '" . $amount . "', " . intval($user_id) . ", 1997, 'not_processed'
			)
		");
	}
*/
	# /hack ...

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'tickets' => $tickets,
		'n_points_processed' => $n_points_processed,
		'user' => $api_user['user'],
	]);

})->assert('user_id', '\d+');


$app->get('/shops/{shop_id}/tickets', function($shop_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

	$shop = $app->db->query("
		SELECT *
		FROM `marchand`
		WHERE `id` = " . intval($shop_id) . "
	")->fetch_assoc();

	$q1 = $app->db->query("
		SELECT 	*
		FROM 	`sinequanone_tickets`
		WHERE 	`merchant_id` = " . intval($shop_id) . "
		ORDER BY `created_at` DESC
		LIMIT 1000

	");

	$tickets = [];
	while ($ticket = $q1->fetch_array(MYSQLI_ASSOC))
		$tickets[] = $ticket;

	foreach ($tickets as $key => $ticket)
		$tickets[$key]['user'] = $app->db->query("
								SELECT *
								FROM `mobileuser`
								WHERE `id` = " . intval($ticket['mobileuser_id']) . "
						    ")->fetch_assoc();

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'shop' => $shop,
		'tickets' => $tickets,
	]);


})->assert('shop_id', '\d+');


#
# post
#

$app->post('/shops/{shop_id}/ticket', function($shop_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

	$amount = str_replace(',', '.', @$_REQUEST['amount']);
	$amount = intval($amount * 100);

	$app->db->query("

		INSERT INTO  `sinequanone_tickets` (
			`ticket_id` ,
			`created_at` ,
			`amount` ,
			`mobileuser_id` ,
			`merchant_id` ,
			`status`
		)
		VALUES (
			'" . date('Ymd') . "00" . $app->db->real_escape_string(@$_REQUEST['ticket_id']) . "',
			NOW(),
			'" . intval($amount) . "',
			'" . intval(@$_REQUEST['user_id']) . "',
			'" . intval($shop_id) . "',
			'not_processed'
		);

	");

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
	]);


})->assert('shop_id', '\d+');



