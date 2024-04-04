<?php
	require_once('utils.php');
	require_once("../../lib/Logger.class.php");
	require_once('dbLogInfo.php');

	if (!isset($logger))
		$logger = new Logger('logs/');

	$logger->log('debug', 'getMerchants2', "in file", Logger::GRAN_MONTH);

	////////////////////////////////////////
	// DataBase Properties
	$tbl_merchant="marchand";
	$tbl_mobileuser="mobileuser";
	$tbl_merchant_has_mobileuser="marchand_has_mobileuser";

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	$error = FALSE;
	$errorMsg = "";

	$merchant_array = array();

	if (!isset($jsonArray->usr_id) || !isset($jsonArray->nb_merchants) || !isset($jsonArray->is_having_points))
	{
		$error = TRUE;
		$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	}
	else
	{
		/// Check if location paramas are present
		$have_location = FALSE;
		if (isset($jsonArray->latitude) && isset($jsonArray->longitude) && !empty($jsonArray->latitude) && !empty($jsonArray->longitude))
			$have_location = TRUE;

		if ($jsonArray->is_having_points == "0")
			$have_point = FALSE;
		else
			$have_point = TRUE;

		/// Get the User
		$sqlGetUser = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			.mysql_real_escape_string($jsonArray->usr_id)
			. "'";

		$result = mysql_query($sqlGetUser);

		if ($result != FALSE && mysql_num_rows($result))
		{
			$user = mysql_fetch_array($result);

			/// Cas ou l'utilisateur n'a pas de points
			if ($have_point == FALSE)
			{
				$sqlGetMerchant = "SELECT * FROM $tbl_merchant";
				$merchantResult = mysql_query($sqlGetMerchant);

				if (mysql_num_rows($merchantResult) && $have_location == TRUE)
				{
					$logger->log('debug', 'getMerchants2', "TEST1", Logger::GRAN_MONTH);

					$req_login = array(
        			"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        			"login" => "youfid",
        			"password" => "youfid"
					);

					$req_login = json_encode($req_login);

					$result = postRequest($url_loyalty . "services/user/login", $req_login);
					$logger->log('debug', 'getMerchants2', "HTTPOSTRESPONSE=" . $result);

					$youfid_access = json_decode($result);
					if (isset($youfid_access->error))
						$youfid_error = $youfid_access->error;

					/// Si le login s'est bien passé, on fouille "merchant_has_mobile_user"
					if (isset($youfid_error->messages[0]) && $youfid_error->messages[0] == "OK")
					{
						$wsAccess = $youfid_access->wsAccess;
						$wsAccessPublicKey = $wsAccess->wsAccessPublicKey;
						$wsAccessToken =  $wsAccess->wsAccessToken;

						//////////////////////////////
						$merchant_to_sort = array();
						$index = 0;

						/// Get all the merchants with distance
						while ($row = mysql_fetch_array($merchantResult))
						{
							$logger->log('debug', 'getMerchants2', "TEST2 + " . $row['is_supermarchand'], Logger::GRAN_MONTH);
							if ($row['is_supermarchand'] == "0" && !empty($row['latittude']) && !empty($row['longitude']))
							{
								$logger->log('debug', 'getMerchants2', "TEST3 + " . $row['is_supermarchand'], Logger::GRAN_MONTH);
								$lat1 = $row['latittude'];
								$lon1 = $row['longitude'];
								$lat2 = $jsonArray->latitude;
								$lon2 = $jsonArray->longitude;

								$distance = distance($lat1, $lon1, $lat2, $lon2);

								//$row['distance'] = $distance;
								//$merchant_to_sort[$index] = $row;

								if (isset($row['application_id']) && !empty($row['application_id']))
								{

									$merchant_to_sort[$index]['application_id'] = $row['application_id'];

									$merchant_to_sort[$index]['merchant_id'] = $row['id'];
									$merchant_to_sort[$index]['merchant_name'] = $row['name'];
									$merchant_to_sort[$index]['logo'] = $row['logo'];
									$merchant_to_sort[$index]['address'] = $row['address'];
									$merchant_to_sort[$index]['latitude'] = $row['latittude'];
									$merchant_to_sort[$index]['longitude'] = $row['longitude'];
									$merchant_to_sort[$index]['distance'] = $distance;


									$index += 1;
								}
							}
						}

						/// tri des marchands: offset + distance
						if (isset($merchant_to_sort))
						{
							/*foreach ($merchant_to_sort as $val)
									$sortArrayDistance[] = $val['distance'];*/
							$logger->log('debug', 'getMerchants2', "Merchant to sort setted...", Logger::GRAN_MONTH);
							$sortArrayDistance = array();
							$i = 0;
							while ($i < $index)
							{
								$sortArrayDistance[$i] = $merchant_to_sort[$i]['distance'];
								$logger->log('debug', 'getMerchants2', "Distance = " . $merchant_to_sort[$i]['distance'], Logger::GRAN_MONTH);
								$i += 1;
							}

			  				array_multisort($sortArrayDistance, $merchant_to_sort);
							foreach ($merchant_to_sort as $val)
							{
								$logger->log('debug', 'getMerchants2', "MerchantList::" . $val['merchant_name'] . " - " . $val['distance'], Logger::GRAN_MONTH);
							}

							/// On ajoute enfin les marchands en fonction du range. Pour le moment les 10 premiers
							for($index = 0; isset($merchant_to_sort[$index]); $index++)
							{
								if ($index < 10)
									$merchant_array[$index] = $merchant_to_sort[$index];
							}
						}

						/// on va chercher le nombre de points
						$index = 0;
						while (isset($merchant_array[$index]))
						{
							$merchant = $merchant_array[$index];

							$merchant_request['mobileUserPublicId'] = $user['public_id'];
							$merchant_request['applicationPublicId'] = $merchant['application_id'];

							/// On cherche une correspondance
							$sqlGetMeHaMoUsr = "SELECT * FROM $tbl_merchant_has_mobileuser WHERE `marchand_id`='"
								. mysql_real_escape_string($merchant['merchant_id'])
								. "' AND `mobileuser_id`='"
								. mysql_real_escape_string($user['id'])
								. "'";

							$result = mysql_query($sqlGetMeHaMoUsr);

							if (mysql_num_rows($result))
							{
								$merchant_request = json_encode($merchant_request);

								$post_result =  postRequest($url_loyalty . "services/mobileuser/mobiuserapp", $merchant_request);
								$logger->log('debug', 'getMerchants2', "HTTPOSTRESPONSE_get Mobile user associated with an application=" . $post_result);

								$post_result = json_decode($post_result);

								if (isset($post_result->error))
									$loyaulty_error = $post_result->error;

								if (isset($loyaulty_error->messages[0]) && $loyaulty_error->messages[0] == "OK")
								{
									$mobileUserApplication = $post_result->mobileUserApplication;

									$nb_point = $mobileUserApplication->totalPoints;
									$merchant_array[$index]['nb_pts'] = $nb_point;
								}
								else {
									$merchant_array[$index]['nb_pts'] = 0;
								}
							}
							else
								$merchant_array[$index]['nb_pts'] = 0;
							$index += 1;
						}
					}


				}
				/// Sinon... Tableau vide! tant pis pour toi
			}

			/*$req_login = array(
        "wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        "login" => "youfid",
        "password" => "youfid"
			);*/

			/// Cas ou le client has_point = 1
			else
			{
				$req_login = array(
        			"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        			"login" => "youfid",
        			"password" => "youfid"
				);

				$req_login = json_encode($req_login);

				$result = postRequest($url_loyalty . "services/user/login", $req_login);
				$logger->log('debug', 'getMerchants2', "HTTPOSTRESPONSE=" . $result);

				$youfid_access = json_decode($result);
				$youfid_error = $youfid_access->error;

				$logger->log('debug', 'getMerchants2', "HTTPOSTRESPONSE_login ErrorMessage=" . $youfid_error->messages[0]);

				/// Si le login s'est bien passé, on fouille "merchant_has_mobile_user"
				if ($youfid_error->messages[0] == "OK")
				{
					$wsAccess = $youfid_access->wsAccess;
					$wsAccessPublicKey = $wsAccess->wsAccessPublicKey;
					$wsAccessToken =  $wsAccess->wsAccessToken;

					$sqlGetMhasMobilUser = "SELECT * FROM $tbl_merchant_has_mobileuser WHERE `mobileuser_id`='"
						. mysql_real_escape_string($user['id'])
						. "'";

					$result = mysql_query($sqlGetMhasMobilUser);

					/// On a trouvé des marchands associés au user
					if ($result != FALSE && mysql_num_rows($result))
					{
						$merchant_to_sort = array();
						$index = 0;
						while ($row = mysql_fetch_array($result))
						{
							$merchant_request = array();
							$merchant_request['wsAccessPublicKey'] = $wsAccessPublicKey;
							$merchant_request['wsAccessToken'] = $wsAccessToken;

							/// Get merchant to have public_id
							$sqlGetMerchant = "SELECT * FROM $tbl_merchant WHERE `id`='"
								. mysql_real_escape_string($row['marchand_id'])
								. "'";

							$resultMerchant = mysql_query($sqlGetMerchant);

							if (mysql_num_rows($resultMerchant))
							{
								$merchant = mysql_fetch_array($resultMerchant);

								$merchant_request['mobileUserPublicId'] = $user['public_id'];
								$merchant_request['applicationPublicId'] = $merchant['application_id'];

								$merchant_request = json_encode($merchant_request);

								$post_result =  postRequest($url_loyalty . "services/mobileuser/mobiuserapp", $merchant_request);
								$logger->log('debug', 'getMerchants2', "HTTPOSTRESPONSE_get Mobile user associated with an application=" . $post_result);

								$post_result = json_decode($post_result);

								$loyaulty_error = $post_result->error;
								if ($loyaulty_error->messages[0] == "OK")
								{
									$mobileUserApplication = $post_result->mobileUserApplication;

									$nb_point = $mobileUserApplication->totalPoints;

									if ($nb_point > 0)
									{
										if ($have_location == TRUE)
										{
											$lat1 = $merchant['latittude'];
											$lon1 = $merchant['longitude'];
											$lat2 = $jsonArray->latitude;
											$lon2 = $jsonArray->longitude;

											$distance = distance($lat1, $lon1, $lat2, $lon2);
											$merchant_to_sort[$index]['distance'] = $distance;
										}

										$merchant_to_sort[$index]['merchant_id'] = $merchant['id'];
										$merchant_to_sort[$index]['merchant_name'] = $merchant['name'];
										$merchant_to_sort[$index]['logo'] = $merchant['logo'];
										$merchant_to_sort[$index]['address'] = $merchant['address'];
										$merchant_to_sort[$index]['latitude'] = $merchant['latittude'];
										$merchant_to_sort[$index]['longitude'] = $merchant['longitude'];
										$merchant_to_sort[$index]['nb_pts'] = strval($nb_point);
										/// nb_use

										$index += 1;
									}
								}
							}


						}

						/// On trie par rapport aux points si pas de location
						if (isset($merchant_to_sort) && $have_location == FALSE)
						{
							/*foreach ($merchant_to_sort as $val)
									$sortArrayDistance[] = $val['distance'];*/
							$logger->log('debug', 'getMerchants2', "Merchant to sort setted...", Logger::GRAN_MONTH);
							$sortArrayPoints = array();
							$i = 0;
							while ($i < $index)
							{
								$sortArrayPoints[$i] = $merchant_to_sort[$i]['nb_pts'];
								$logger->log('debug', 'getMerchants2', "nb_points = " . $merchant_to_sort[$i]['nb_pts'], Logger::GRAN_MONTH);
								$i += 1;
							}

			  				array_multisort($sortArrayPoints, SORT_DESC, $merchant_to_sort);
							foreach ($merchant_to_sort as $val)
							{
								$logger->log('debug', 'getMerchants2', "MerchantList::" . $val['merchant_name'] . " - " . $val['nb_pts'], Logger::GRAN_MONTH);
							}

							/// On ajoute enfin les marchands en fonction du range. Pour le moment les 10 premiers
							for($index = 0; isset($merchant_to_sort[$index]); $index++)
							{
								if ($index < 10)
									$merchant_array[$index] = $merchant_to_sort[$index];
							}
						}
						/// On trie par rapport a la distance
						else if (isset($merchant_to_sort) && $have_location == TRUE)
						{
							$logger->log('debug', 'getMerchants2', "Merchant to sort setted...", Logger::GRAN_MONTH);

							$sortArrayDistance = array();
							$i = 0;
							while ($i < $index)
							{
								$sortArrayDistance[$i] = $merchant_to_sort[$i]['distance'];
								$logger->log('debug', 'getMerchants2', "Distance = " . $merchant_to_sort[$i]['distance'], Logger::GRAN_MONTH);
								$i += 1;
							}

			  				array_multisort($sortArrayDistance, $merchant_to_sort);
							foreach ($merchant_to_sort as $val)
							{
								$logger->log('debug', 'getMerchants2', "MerchantList::" . $val['merchant_name'] . " - " . $val['distance'], Logger::GRAN_MONTH);
							}

							/// On ajoute enfin les marchands en fonction du range. Pour le moment les 10 premiers
							for($index = 0; isset($merchant_to_sort[$index]); $index++)
							{
								if ($index < 10)
									$merchant_array[$index] = $merchant_to_sort[$index];
							}
						}


						$logout_request['token'] = $wsAccessToken;
						$logout_request = postRequest($url_loyalty . "services/user/logout/", json_encode($logout_request));
					}
				}
				else
				{
					//////////////////////////////////////////////////////////////////
					/// Gerer error ou non!
					$error = TRUE;
					$errorMsg = "Error: Problem during login to Youfid:" . json_encode($youfid_access['error']);
				}


				/*"wsAccess" : {
					"wsAccessPublicKey" : "keyxxxxxxxxxxxxxxxx",
					"wsAccessSuspend" : "false",
					"wsAccessToken" : "tokenxxxxxxxxxxx",
					"wsTokenStartTime" : "13/12/2012 10:44:15",
					"user" : {
						"name" : "Youfid"
						"login" : "youfid"
						"email" : "test@youfid.com"
						"company" : "4G Secure"
						"role" : {
							"name" : "CLIENT"
						}
					}
				}*/
			}
		}
		else
		{
			$error = TRUE;
			$errorMsg = "Error: No user found with this user_id";
		}
	}

	$jsonResult = array();

	if ($error == TRUE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult['status'] = "ok";
		$jsonResult['merchants'] = $merchant_array;
	}

	echo(json_encode(array_map_utf8_encode($jsonResult)));
