<?php

#
# get
#
$app->get('/shops/from_legacy/getMerchants', function() use ($app) {

	if ($_GET['order_by'] == 'points')
	{
		$_GET['offset'] = 0;
		$_GET['nb_merchants'] = 100;
	}

	if ($_GET['order_by'] == 'alpha' and $_GET['offset'])
	{
		$_GET['offset'] = 0;
	}

	$_GET['search'] = trim(strtolower(@$_GET['search']));
	if ($_GET['search'])
	{
		$_GET['offset'] = 0;
		$_GET['nb_merchants'] = 100;
	}

	$client = new \GuzzleHttp\Client();
	$response = json_decode($client->post('http://api.youfid.fr/dev/service/getMerchants.php', [
	    'timeout' => 20,
	    'json' => $_GET,
	    'headers' => apache_request_headers(),
	])->getBody(), true);

	if($response['status'] != 'ok')
	    return $app->json([
			'status' => [
				'code' => 400,
				'message' => $response['message'],
			],
			'_GET' => $_GET,
			'shops' => [],
		]);

	$merchants = $response['merchants'];

	# data cleaning ...

	foreach ($merchants as $key => $merchant)
		if($merchant['merchant_id'] == 1685 or $merchant['merchant_id'] == 1997)
			unset($merchants[$key]);

	foreach ($merchants as $key => $merchant)
		$merchants[$key]['label'] = strtolower(@$app->db->query("
			SELECT * FROM `label` WHERE `id` = " . intval($merchant['label_id']) . "
		")->fetch_array(MYSQLI_ASSOC)['nom']);


	if ($_GET['order_by'] == 'points')
	{
		$_merchants = [];
		foreach ($merchants as $m)
			$_merchants[max(0, $m['nb_pts']) . '_' . $m['merchant_id']] = $m;
		krsort($_merchants);
		$merchants = $_merchants;
	}

	if ($_GET['order_by'] == 'alpha')
	{
		$_merchants = [];
		foreach ($merchants as $m)
			$_merchants[$m['merchant_name'] . '_' . $m['merchant_id']] = $m;
		ksort($_merchants);
		$merchants = $_merchants;
	}

	if ($_GET['search'])
	{
		$q = strtolower($_GET['search']);
		$searches = array_merge([$q], explode(' ', $q));

		foreach ($searches as $key => $search) $searches[$key] = trim($search);
		foreach ($searches as $key => $search) if(!$search) unset($searches[$key]);

		$_merchants = [];
		foreach ($merchants as $m)
			foreach ($searches as $search)
			if(strstr(strtolower($m['merchant_name']), $search) or $m['label'] == $search)
				$_merchants[$m['merchant_id']] = $m;
		$merchants = $_merchants;
	}

	$shops = [];
	foreach ($merchants as $merchant)
		$shops[] = $merchant;

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'_GET' => $_GET,
		'searches' => $searches,
		'shops' => $shops,
	]);

});

#
# get
#
$app->get('/shops/super/{shop_id}', function($shop_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

	$q1 = $app->db->query("
		SELECT *
		FROM `marchand`
		WHERE `supermarchand_id` = " . intval($shop_id) . "
	");

	$shops = [];
	while ($shop = $q1->fetch_array(MYSQLI_ASSOC))
		$shops[] = $shop;

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'shops' => $shops,
	]);

})->assert('shop_id', '\d+');


#
# filters
#
$app->get('/shops/super/{shop_id}/filters', function($shop_id) use ($app) {

	$filters = [];

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'filters' => $filters,
	]);

})->assert('shop_id', '\d+');
