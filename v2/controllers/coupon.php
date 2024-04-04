<?php

#
# get
#

$app->get('/shops/{shop_id}/coupons', function($shop_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json(['status' => ['code' => 401, 'message' => 'unauthorized access, token is invalid']]);

	$curl = curl_init('http://api.youfid.fr/dev/service/marchandGetMerchantDetailsV2.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('merchant_id' => $shop_id)));
	$response = curl_exec($curl);
	curl_close($curl);
	$products = @json_decode($response)->products;

	$coupons = [];
	foreach ($products as $product)
		$coupons[] = [
			'id' => intval($product->id),
			'name' => $product->name,
			'code' => 'FIDSN' . $product->cost . 'E',
			'n_points' => intval($product->cost),
		];

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'coupons' => $coupons,
	]);


})->assert('shop_id', '\d+');



$app->get('/shops/{shop_id}/users/{user_id}/coupons', function($shop_id, $user_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json(['status' => ['code' => 401, 'message' => 'unauthorized access, token is invalid']]);

	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/users/' . $user_id . '?access_token=' . $_REQUEST['access_token'];
	$api_user = json_decode(file_get_contents($api_url), true);
	if($api_user['status']['code'] != 200)
		return $app->json($api_user);

	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/coupons?access_token=' . $_REQUEST['access_token'];
	$api_coupons = json_decode(file_get_contents($api_url), true);

	$coupons = [];
	foreach ($api_coupons['coupons'] as $coupon)
		if($coupon['n_points'] <= $api_user['user']['n_points'])
			$coupons[] = $coupon;


	# sinequanone
	if($shop_id == 1585)
		if(@substr($api_user['user']['fid_status'], -1) == '1')
			$coupons[] = [
				'id' => 0,
				'name' => '-15% de réduction',
				'code' => 'FIDSN15P',
				'n_points' => 0,
			];


    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'coupons' => $coupons,
		'user' => $api_user['user'],
	]);


})->assert('user_id', '\d+');



$app->get('/shops/{shop_id}/users/{user_id}/burned', function($shop_id, $user_id) use ($app) {

	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/users/' . $user_id . '/coupons?access_token=' . $_REQUEST['access_token'];
	$api_user = json_decode(file_get_contents($api_url), true);
	if($api_user['status']['code'] != 200)
		return $app->json($api_user);

    $user = $app->db->query("
		SELECT * FROM `mobileuser` WHERE `id` = " . intval($user_id) . "
    ")->fetch_assoc();

    $q2 = $app->db2->query("
		SELECT `transaction`.*
		FROM `transaction`
		LEFT JOIN `mobile_user` ON `mobile_user`.`id` = `transaction`.`mobile_user_id`
		WHERE
			`mobile_user`.`public_id` LIKE '" . $user['public_id'] . "' AND
			`transaction`.`type` LIKE 'WITHDRAW_POINTS' AND
			`transaction`.`application_id` = 1395
    "); # application_id = 1395, Sinequanone

	$burned = [];
	$n_points_burned = 0;
	while ($withdraw = $q2->fetch_array(MYSQLI_ASSOC))
	{
		$burned[] = [
			'created_at' => $withdraw['creation'],
			'n_points' => $withdraw['point'],
		];
		$n_points_burned += $withdraw['point'];
	}

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'burned' => $burned,
		'n_points_burned' => $n_points_burned,
		'user' => $api_user['user'],
	]);


})->assert('user_id', '\d+');


#
# post
#


$app->post('/shops/{shop_id}/users/{user_id}/coupons/burn-code/{code}', function($shop_id, $user_id, $code) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json(['status' => ['code' => 401, 'message' => 'unauthorized access, token is invalid']]);

	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/users/' . $user_id . '?access_token=' . $_REQUEST['access_token'];
	$api_user = json_decode(file_get_contents($api_url), true);
	if($api_user['status']['code'] != 200)
		return $app->json($api_user);


	# sinequanone
	if($code == 'FIDSN15P')
	{
		if(@substr($api_user['user']['fid_status'], -1) == '0')
		    return $app->json([
				'status' => [
					'code' => 403,
					'message' => 'user cannot use this code',
				],
			]);

		$fid_status = floor($api_user['user']['fid_status'] / 10) * 10;
	    $q = $app->db->query("
	    	UPDATE `mobileuser`
	    	SET `fid_status` = '" . $fid_status . "'
	    	WHERE `id` = " . intval($user_id) . "
	    ");
	}



	$api_url = 'http://' . $_SERVER['SERVER_NAME'] . '/v2/shops/' . $shop_id . '/coupons?access_token=' . $_REQUEST['access_token'];
	$api_coupons = json_decode(file_get_contents($api_url), true);

	$products = [];
	foreach ($api_coupons['coupons'] as $coupon)
		if($coupon['code'] == $code)
			$products[] = [
				'id' => $coupon['id'],
				'cost' => $coupon['n_points'],
				'name' => $coupon['name'],
			];

	/*
		URL	http://api.youfid.fr/dev/service/transformToPresent.php
		{
			"merchant_id": "1575",
			"usr_id": "670465",
			"products": [{
				"id": "4731",
				"cost": "100",
				"name": "8€ de réduction"
			}]
		}
	*/

	$curl = curl_init('http://api.youfid.fr/dev/service/transformToPresent.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('merchant_id' => $shop_id, 'usr_id' => $user_id, 'products' => $products)));
	$response = curl_exec($curl);
	curl_close($curl);
	$products = @json_decode($response)->status;

	if(@json_decode($response)->status == 'ok')
	    return $app->json([
			'status' => [
				'code' => 200,
				'message' => 'ok',
			],
		]);

    return $app->json([
		'status' => [
			'code' => 500,
			'message' => 'action failed',
		],
	]);


})->assert('user_id', '\d+');
