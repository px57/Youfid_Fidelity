<?php
	function putRequest($url, $param)
	{
		
		
		$ch_add_app = curl_init($url);
		 
		curl_setopt($ch_add_app, CURLOPT_CUSTOMREQUEST, "PUT"); 
		curl_setopt($ch_add_app, CURLOPT_POSTFIELDS, $param);
		curl_setopt($ch_add_app, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch_add_app, CURLOPT_HTTPHEADER, array(                                                                          
	    		'Content-Type: application/json',                                                                                
	    		'Content-Length: ' . strlen($param))                                                                       
		);
		
		curl_setopt($ch_add_app, CURLOPT_RETURNTRANSFER, true);
		 
		$response = curl_exec($ch_add_app);
		curl_close($ch_add_app);
		
		
		
		return $response;
	}
	
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$marchand_id = $_SESSION['selector'];
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($_POST['bienvenue'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET offre_bienvenue='" . $_POST['bienvenue'] . "' WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	if (isset($_POST['points'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET points_for_accueil=" . $_POST['points'] . " WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	if (isset($_POST['txt_exp'])) {
		$updateMarchand = "UPDATE " . $tbl_marchands . " SET texte_explicatif='" . $_POST['txt_exp'] . "' WHERE id = '" . $_SESSION['selector'] . "'";
		$resultUp = mysql_query($updateMarchand);
	}
	
	if (isset($_POST['inRulePoint']) || isset($_POST['inRuleCash'])) {
		//LOGIN//
		$login_url = $url_loyalty . 'services/user/login';
		$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
		$result =  postRequest($login_url, $json_login);
		$loginResult = json_decode($result, true);
		/////////
		
		$tbl_name1 = "marchand";
		$sqlGetMarchand = "SELECT * FROM $tbl_name1 WHERE `id` = '"
				. mysql_real_escape_string($marchand_id)
				. "'";
		$result2 = mysql_query($sqlGetMarchand);
		$rowMarchand = mysql_fetch_array($result2);
		
		////////
		$jsonApp = '{
				"wsAccessPublicKey" 	:  "8293582c-1e0c-40ff-9d59-10cb18834855",
				"wsAccessToken" 		: "' . $loginResult['wsAccess']['wsAccessToken'] . '",
				"applicationPublicId" 	: "' . $rowMarchand['application_id'] . '"
				}';
				
		$appUrl = $url_loyalty . "services/application/get";
		$appResult = postRequest($appUrl, $jsonApp);
		$appJson = json_decode($appResult, true);
		
		$upmarurl = $url_loyalty . "services/application";
		$jsonArray['wsAccessPublicKey'] = "8293582c-1e0c-40ff-9d59-10cb18834855";
		$jsonArray['wsAccessToken'] = $loginResult['wsAccess']['wsAccessToken'];
		$jsonArray['publicId'] = $appJson['application']['publicId'];
		$jsonArray['name'] =$appJson['application']['name'];
		$jsonArray['currency'] = "EUR";
		$jsonArray['inRuleCash'] = intval($_POST['inRuleCash']);
		$jsonArray['inRulePoint'] = intval($_POST['inRulePoint']);
		$jsonArray['outRuleCash'] = $appJson['application']['outRuleCash'];
		$jsonArray['outRulePoint'] = $appJson['application']['outRulePoint'];
		$jsonArray['lifetime'] = $appJson['application']['lifetime'];
		$jsonArray['minimumToTransform'] = $appJson['application']['minimumToTransform'];
		putRequest($upmarurl, json_encode($jsonArray));
	}
	
	header("location:../youfid_master_programmedefid.php");
	?>
