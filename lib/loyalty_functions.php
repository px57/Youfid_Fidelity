<?php

	/////////////////////////////////////////////////////////////////////////////////
	//// Contient des fonction pour contacter LOYALTY
	
	require_once("Logger.class.php");
	require_once('../dev/service/utils.php');
	
	if (!isset($logger))
		$logger = new Logger('../logs/');
	
	$logger->log('debug', 'loyalty_functions', "in file", Logger::GRAN_MONTH);
	
	///////////////////
	/// SETTINGS
	///////////////////
	
	// Urls
	$service_base_url = $url_loyalty . "services/";
	$login_service = "user/login";
	$add_application_service = "application";

	/**
	 * 	loyalty_login()
	 *  Effectue le login LOYALTY et renvois le code d'erreur (200 si le login est un succes)
	 * 
	 * 	@return array youfidAcces: tableau contenant les codes d'erreur du login ainsi que les acces loyalty
	 */

	function doLoyaltyLogin()
	{
		global $logger, $url_loyalty;
		
		$req_login = array(
        	"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
        	"login" => "youfid",
        	"password" => "youfid"
			);
				
		$req_login = json_encode($req_login);
					
		$result = postRequest($url_loyalty . "services/user/login", $req_login);
		//$logger->log('debug', 'getMerchants_V2', "inLogin::response=" . $result);
					
		$youfid_access = json_decode($result);
		
		if (isset($youfid_access->error))
			$youfid_error = $youfid_access->error;
		else
			return FALSE;
		
		/// Si errorMessage == "OK" => on retourne un array youFidAccess
		if (isset($youfid_error->messages[0]) && $youfid_error->messages[0] == "OK")
			return $youfid_access;
		
		return FALSE;
	}
	
	/**
	 * 	loyalty_addApplication(String, String, ...)
	 *  Ajoute une application sur le serveur de LOYALTY
	 * 
	 *  @param string $name nom du marchand
	 * 	@param string $publicId equivalent de "application_id" en DB
	 * 	@param ... non mandatory
	 * 
	 */
	
	function loyalty_addApplication($name, $publicId, $inRuleCash = 0, $inRulePoint = 0, $outRuleCash = 0, $outRulePoint = 0,
		$lifetime = 10000, $currency = "EUR", $minimumToTransform = 0)
	{
		global $logger, $service_base_url, $add_application_service, $url_loyalty;
		
		// Do login
		$loyalty_login = doLoyaltyLogin();
		if ($loyalty_login == FALSE)
			return FALSE;
		
		$wsAccess = $loyalty_login->wsAccess;
		
		$req_add_application = array(
			"wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
			"wsAccessToken" => $wsAccess->wsAccessToken,
			"publicId" => $publicId,
			"name" => $name,
			"currency" => $currency,
			"inRuleCash" => $inRuleCash,
			"inRulePoint" => $inRulePoint,
			"outRuleCash" => $outRuleCash,
			"outRulePoint" => $outRulePoint,
			"lifetime" => $lifetime,
			"minimumToTransform" => $minimumToTransform
		);
		
		$req_add_application_json = json_encode($req_add_application);
		$logger->log('debug', 'loyalty_functions', "loyalty_addApplication::request=" . $req_add_application_json, Logger::GRAN_MONTH);
		
		$result = postRequest($service_base_url.$add_application_service, $req_add_application_json);
		
		$loyalty_result = json_decode($result);
		
		$logger->log('debug', 'loyalty_functions', "loyalty_addApplication::response=" . $result, Logger::GRAN_MONTH);
		
		if (isset($loyalty_result->error))
			$loyalty_error = $loyalty_result->error;
		else
			return FALSE;
		
		if (isset($loyalty_error->messages[0]) && $loyalty_error->messages[0] == "OK")
			return TRUE;
		
		return FALSE;
	}

?>
