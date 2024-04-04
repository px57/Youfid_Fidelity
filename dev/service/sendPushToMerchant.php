<?php
	/// table name
	$tbl_name="marchand_has_mobileuser";
	require_once('dbLogInfo.php');
	require_once('utils.php');
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	$error = false;
	$errorMsg = "";
	if (isset($jsonArray->usr_id) && isset($jsonArray->merchant_id))
	{
			$usr_id = $jsonArray->usr_id;
			$merchant_id = $jsonArray->merchant_id;
			$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
			. mysql_real_escape_string($jsonArray->usr_id)
			. "' AND `marchand_id` = '"
			. mysql_real_escape_string($jsonArray->merchant_id)
			. "'";
			$resultex = mysql_query($sqlGetCustomer);
			$rowNb = mysql_num_rows($resultex);
			if ($rowNb == 0) // Relation jamais créée, donc création.
			{
				$sqlGetMarchand = "SELECT * FROM marchand WHERE `id` = '"
								. mysql_real_escape_string($merchant_id)
								. "'";
				$resultMarchand = mysql_query($sqlGetMarchand);
				if (mysql_num_rows($resultMarchand) == 0)
					{
						$error = true;
						$errorMsg = "No Such merchant";
					}
				else {
					$rowMarchand = mysql_fetch_array($resultMarchand);
					//////////////
					/// Get public user ID
					$sqlGetUser = "SELECT * FROM mobileuser WHERE `id` = '"
								. mysql_real_escape_string($usr_id)
								. "'";
					$result2 = mysql_query($sqlGetUser);
					if (mysql_num_rows($result2) == 0)
					{
						$error = true;
						$errorMsg = "No Such user";
					}
					else {
						$rowUser = mysql_fetch_array($result2);
						$createRelation = "INSERT INTO  $tbl_name SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($usr_id)
					. "', nb_use='"
					. mysql_real_escape_string("0")
					. "'";
					$resultInsert = mysql_query($createRelation);
					if ($resultInsert == FALSE){
						$error = true;
						$errorMsg = "Error with the db::" . $sqlApp;
					}
					else {
						//inscription
						$json_insri = '{
						"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
						"wsAccessToken" : "' . $loginResult['wsAccess']['wsAccessToken'] . '",
						"mobileUserPublicId" : "'. $rowUser['public_id']  . '",
						"applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
						"points" : 0
						}';
						$inscri_url = $url_loyalty . "services/mobileuser";
						$result_inscri =  postRequest($inscri_url, $json_insri);
					}
				}
			}
		}
		if ($error == FALSE)
		{
			$sqlUpdate = "UPDATE $tbl_name SET date_localisation=NOW() WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($jsonArray->usr_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($jsonArray->merchant_id)
						. "'";
			$resultUpdate = mysql_query($sqlUpdate);
			if ($resultUpdate == FALSE){
				$error = true;
				$errorMsg = "Error with the db::" . $sqlApp;
			}
		}
	}
	else {
		$error = true;
		$errorMsg = "Error with parameters";
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
		$jsonResult['message'] = "";
	}

	echo(json_encode(array_map_utf8_encode($jsonResult)));



