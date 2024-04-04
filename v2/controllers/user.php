<?php

#
# get
#
$app->get('/shops/{shop_id}/users/{user_id}', function($shop_id, $user_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

    $user = $app->db->query("
		SELECT *
		FROM `mobileuser`
		WHERE `id` = " . intval($user_id) . "
    ")->fetch_assoc();

    if(!$user)
	    return $app->json([
			'status' => [
				'code' => 204,
				'message' => 'user not found',
			],
		]);


	$curl = curl_init('http://api.youfid.fr/dev/service/getMerchantDetails.php');
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('usr_id' => $user_id, 'merchant_id' => $shop_id)));
	$response = curl_exec($curl);
	curl_close($curl);
	$n_points = @json_decode($response)->nb_pts;


    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'user' => [

    		'id' => intval($user_id),
    		'url' => 'http://api.youfid.fr/v2/shops/' . $shop_id . '/users/' . $user_id,

    		'qr_code' => $user['qr_code'],
    		'email' => $user['mail'],
    		'phone' => $user['phone'],
    		'first_name' => $user['prenom'],
    		'last_name' => $user['nom'],
    		'location' => [
    			#'country' => $user['xxxxxx'],
    			'zipcode' => $user['zip'],
    			'address' => $user['address'],
    			'latitude' => $user['lattitude'],
    			'longitude' => $user['longitude'],
    		],

            'n_points' => max(0, intval($n_points)),
    		'n_points_real' => intval($n_points),

    		'fid_status' => $user['fid_status'],

    		'updated_at' => $user['updated_at'],
    		'created_at' => $user['date_inscription'],

		],
	]);

})->assert('user_id', '\d+');

#
# get
#
$app->get('/shops/{shop_id}/users/search', function($shop_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

	if(!@$_REQUEST['email'] and !@$_REQUEST['qr_code'])
	    return $app->json([
			'status' => [
				'code' => 400,
				'message' => 'bad request, qr_code or email parameter missing',
			],
		]);

	if(@$_REQUEST['email'] and !filter_var(@$_REQUEST['email'], FILTER_VALIDATE_EMAIL))
	    return $app->json([
			'status' => [
				'code' => 400,
				'message' => 'bad request, email parameter is misformatted',
			],
		]);

	if(@$_REQUEST['email'])
	    $q = $app->db->query("
			SELECT *
			FROM `mobileuser`
			WHERE `mail` LIKE '" . $app->db->real_escape_string($_REQUEST['email']) . "'
	    ");

	if(@$_REQUEST['qr_code'] and !is_numeric($_REQUEST['qr_code']))
	    return $app->json([
			'status' => [
				'code' => 400,
				'message' => 'bad request, qr_code parameter is misformatted',
			],
		]);

	if(@$_REQUEST['qr_code'])
	    $q = $app->db->query("
			SELECT *
			FROM `mobileuser`
			WHERE `qr_code` LIKE '" . $app->db->real_escape_string($_REQUEST['qr_code']) . "'
	    ");


    $users = [];
    while ($entry = $q->fetch_assoc())
        $users[] = [
    		'id' => intval($entry['id']),
    		'url' => 'http://api.youfid.fr/v2/shops/' . $shop_id . '/users/' . $entry['id'],
    	];

    if(!$users)
	    return $app->json([
			'status' => [
				'code' => 204,
				'message' => 'zero user found',
			],
		]);

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
		'users' => $users,
	]);

});



#
# edit email
#
$app->post('/shops/{shop_id}/users/{user_id}/email', function($shop_id, $user_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

    $user = $app->db->query("
		SELECT *
		FROM `mobileuser`
		WHERE `id` != " . intval($user_id) . " AND `mail` LIKE '" . $app->db->real_escape_string($_REQUEST['email']) . "'
    ")->fetch_assoc();

    if($user)
	    return $app->json([
			'status' => [
				'code' => 400,
				'message' => 'email already used',
			],
		]);


    $app->db->query("
		UPDATE `mobileuser`
		SET `mail` = '" . $app->db->real_escape_string($_REQUEST['email']) . "'
		WHERE `id` = " . intval($user_id) . "
    ");

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
	]);

})->assert('user_id', '\d+');


#
# edit phone
#
$app->post('/shops/{shop_id}/users/{user_id}/phone', function($shop_id, $user_id) use ($app) {

	if(@$_REQUEST['access_token'] != ACCESS_TOKEN)
	    return $app->json([
			'status' => [
				'code' => 401,
				'message' => 'unauthorized access, token is invalid',
			],
		]);

    $app->db->query("
		UPDATE `mobileuser`
		SET `phone` = '" . $app->db->real_escape_string($_REQUEST['phone']) . "'
		WHERE `id` = " . intval($user_id) . "
    ");

    return $app->json([
		'status' => [
			'code' => 200,
			'message' => 'ok',
		],
	]);

})->assert('user_id', '\d+');
