<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("loyalty_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	$logger->log('debug', 'commerciaux_register_marchand', "in file", Logger::GRAN_MONTH);
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$tbl_label = "label";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	/// Parametres d'erreur
	$error = FALSE;
	$new_merchant_error = "";
	$error_msg = "Erreur dans le formulaire. Veuillez verifier: ";
	
	///////////////////////////////////////////////////////////////////////////////////////////
	/// Declaration des variables marchandes telles que definies dans "new_merchant.php"
	$name = "Nom du marchand";
	$phone = "Téléphone du marchand";
	$address = "Addresse du marchand";
	$city = "Ville du marchand";
	$zip_code = "Code postal du marchand";
	
	$website = "Site web du marchand";
	$fb_page = "Page Facebook du marchand";
	$contact = "Contact:";
	$horaires = "Horaires";
	$email_bo = "Email backoffice";

	if(isset($_POST['name']) && !empty($_POST['name']))
	{
		///////////////////////////////////////////////////////////////////////////////////////////
		/// Bind des champs si ils ont ete laissés par defaut
		if ($name != $_POST['name'])
			$name = $_POST['name'];
		else
		{
			$error = TRUE;
			$new_merchant_error = $error_msg . "le nom du marchand.";
			$name = "";
		}
		
		if ($email_bo != $_POST['email_bo'])
			$email_bo = $_POST['email_bo'];
		else
		{
			$error = TRUE;
			$new_merchant_error = $error_msg . "l'adresse de gestion du backoffice.";
			$email_bo = "";
		}
		
		if ($phone != $_POST['phone'])
			$phone = $_POST['phone'];
		else
			$phone = "";	
		
		/// Get the adress /////
		if ($address != $_POST['address'])
			$address = $_POST['address'];
		else
			$address = "";	
		
		if ($city != $_POST['city'])
			$city = $_POST['city'];
		else
			$city = "";
		
		if ($zip_code != $_POST['zip_code'])
			$zip_code = $_POST['zip_code'];
		else
			$zip_code = "";
		
		///////////////////////
		
		$category = $_POST['categorylist'];
		
		if ($website != $_POST['website'])
			$website = $_POST['website'];
		else
			$website = "";
		
		if ($fb_page != $_POST['fb_page'])
			$fb_page = $_POST['fb_page'];
		else {
			$fb_page = "";
		}
		
		if ($contact != $_POST['contact'])
			$contact = $_POST['contact'];
		else
			$contact = "";
		
		if ($contact != $_POST['horaires'])
			$horaires = $_POST['horaires'];
		else
			$horaires = "";
		
		if (isset($_POST['promolimit']) && !empty($_POST['promolimit']))
		{
			$promolimit = $_POST['promolimit'];
			if (is_numeric($promolimit) == FALSE)
			{
				$error = true;
				$new_merchant_error = $error_msg . "la limite de promotion doit etre un nombre compris entre 0 et 30.";
			}
			else if ($promolimit < 0){
				$error = true;
				$new_merchant_error = $error_msg . "la limite de promotion doit etre un nombre superieur a 0.";
			}
		}
		else
			$promolimit = 0;
		
		if (isset($_POST['maxscan']) && !empty($_POST['maxscan']))
		{
			$maxscan = $_POST['maxscan'];
			if (is_numeric($maxscan) == FALSE)
			{
				$error = true;
				$new_merchant_error = $error_msg . "le nombre de scan doit etre un nombre compris entre 0 et 30.";
			}
			else if ($maxscan < 0){
				$error = true;
				$new_merchant_error = $error_msg . "le nombre de scan doit etre un nombre superieur a 0.";
			}
		}
		else
		{
			$maxscan = 0;
		}
		
		if (isset($_POST['clientmarchandswitch']))
			$is_accueil_client = "0";
		else
			$is_accueil_client = "1";
		
		if (isset($_POST['emailing']))
			$is_emailing = "1";
		else
			$is_emailing = "0";
			
		if (isset($_POST['pinmarchand']))
			$is_pin_marchand = "1";
		else
			$is_pin_marchand = "0";
		
		if (isset($_POST['signalezmarchand']))
			$is_signalez_vous = "1";
		else
			$is_signalez_vous = "0";
		
		if (isset($_POST['is_supermarchand']) && $_POST['is_supermarchand'] == "on")
			$is_supermarchand = "1";
		else
			$is_supermarchand = "0";
		
		if (isset($_POST['is_active']))
			$is_active = "1";
		else
			$is_active = "0";
		
		if (isset($_POST['pin_code']) && !empty($_POST['pin_code']))
			$pin_code = $_POST['pin_code'];
		else
			$pin_code = "";
		
		/// Get longitude/latitude From Google API's
		$longitude = "";
		$latitude = "";
		//$coords=getXmlCoordsFromAdress("22 rue rambuteau, 75003 PARIS, france");
		$coords = getXmlCoordsFromAdress($address . ", " . $zip_code . " " . $city . ", france"); // Only France for the moment
		if (!isset($coords['status']) && empty($coords['status']))
		{
			$error = TRUE;
			$error_msg = "Error: Error during getting longitude/latitude from service.";
		}
		else 
		{
			$latitude = $coords['lat'];
			$longitude = $coords['lon'];
		}
		
		$application_id = "";
		
		////////////////////////////////////////////////////////////////////////////////////////
		/// Gestion des logos
		$logo = "";
		$logo_mini = "";
		
		if ($error == FALSE)
		{
			////////////////////////////////////////////////////////////////////////////////////
			/// UPDATE D'UN MARCHAND EN BDD
			if (isset($_SESSION['selector']) && $_SESSION['selector'] != 0)
			{
				$sqlGetMarchand = "SELECT * FROM $tbl_marchands WHERE `id`='"
					. mysql_real_escape_string($_SESSION['selector'])
					. "'";
					
				$result = mysql_query($sqlGetMarchand);
				if (mysql_num_rows($result))
				{
					$merchantRow = mysql_fetch_array($result);
					
					if ((isset($_FILES['logopath']['error']) && $_FILES['logopath']['error'] == 0) || (isset($_POST['logourl']) && !empty($_POST['logourl'])))
					{
						if (isset($_FILES['logopath']['error']) && $_FILES['logopath']['error'] == 0)
						{
							$logger->log('debug', 'commerciaux_register_marchand', "UPDATELOGO::CASE_1", Logger::GRAN_MONTH);
							require_once('picture_transfer.php');
						}
						else if (isset($_POST['logourl']) && $_POST['logourl'] != $merchantRow['logo'])
						{
							$logger->log('debug', 'commerciaux_register_marchand', "UPDATELOGO::CASE_2", Logger::GRAN_MONTH);
							require_once('picture_transfer.php');
						}
						else
						{
							$logger->log('debug', 'commerciaux_register_marchand', "UPDATELOGO::CASE_3", Logger::GRAN_MONTH);
							$logo = $merchantRow['logo'];
						}
					}
					else
						$logo = $merchantRow['logo'];
					
					$logger->log('debug', 'commerciaux_register_marchand', "UPDATELOGO::CASE_4", Logger::GRAN_MONTH);
					
					$sqlGetCategory = "SELECT * FROM label WHERE `nom` = '"
						. mysql_real_escape_string($category)
						. "'";
						
					$result = mysql_query($sqlGetCategory);
					if ($categoryRow = mysql_fetch_array($result))
					{
						$res = -1;
						$supermarchand_id = -1;
						
						/// Attention un superMarchand cree un nouveau label, mais ne le prends pas.
						if ($is_supermarchand == "1")
						{
							$category = $categoryRow['id']; 
							$res = INSERT_LABEL($name, "franchise");
						}
						else 
						{	
							$category = $categoryRow['id'];
							
							/// Get the supermarchand_id
							$supermarchand_id = getSupermerchantId($category);
							if (isset($supermarchand_id))
								$logger->log('debug', 'commerciaux_register_marchand', "supermarchand_id=".$supermarchand_id, Logger::GRAN_MONTH);
						}
					}
					
					$logger->log('debug', 'commerciaux_register_marchand', "supermarchand_id=".$supermarchand_id . " category=". $category. " res=" . $res, Logger::GRAN_MONTH);
					
					if ($category && $supermarchand_id && $res)
					{
						$loyalty_result = TRUE;
						$is_activation = FALSE;
						
						/// Creation du marchand sur Loyalty
						if ($is_active == "1" && empty($merchantRow['application_id']))
						{
							$application_id = gen_uuid();
							$loyalty_result = loyalty_addApplication($name, $application_id);
						
							$is_activation = TRUE;	
							$logger->log('debug', 'commerciaux_register_marchand', "Go for merchant registration::result=" . $loyalty_result, Logger::GRAN_MONTH);
						}
						else {
							$application_id = $merchantRow['application_id'];
						}
						
						$sqlUpdateMerchant = "UPDATE $tbl_marchands SET contact='"
						 	. mysql_real_escape_string($contact)
							. "', name='"
							. mysql_real_escape_string($name)
							. "', phone='"
							. mysql_real_escape_string($phone)
							. "', address='"
							. mysql_real_escape_string($address)
							
							//
							. "', city='"
							. mysql_real_escape_string($city)
							. "', zip_code='"
							. mysql_real_escape_string($zip_code)
							. "', longitude='"
							. mysql_real_escape_string($longitude)
							. "', latittude='"
							. mysql_real_escape_string($latitude)
							. "', logo='"
							. mysql_real_escape_string($logo)
							. "', logo_mini='"
							. mysql_real_escape_string($logo_mini)
							. "', pin_code='"
							. mysql_real_escape_string($pin_code)
							
							
							//
							. "', site_internet='"
							. mysql_real_escape_string($website)
							. "', page_fb='"
							. mysql_real_escape_string($fb_page)
							. "', horaire='"
							. mysql_real_escape_string($horaires)
							. "', label_id='"
							. mysql_real_escape_string($category)
							. "', is_accueil_client='"
							. mysql_real_escape_string($is_accueil_client)
							. "', is_signalez_vous='"
							. mysql_real_escape_string($is_signalez_vous)
							. "', is_pin_marchand='"
							. mysql_real_escape_string($is_pin_marchand)
							. "', is_email_actif='"
							. mysql_real_escape_string($is_emailing)
							. "', is_active='"
							. mysql_real_escape_string($is_active)
							. "', is_supermarchand='"
							. mysql_real_escape_string($is_supermarchand)
							. "', supermarchand_id='"
							. mysql_real_escape_string($supermarchand_id)
							. "', email_backoffice='"
							. mysql_real_escape_string($email_bo)
							. "', application_id='"
							. mysql_real_escape_string($application_id)
							. "', max_scan='"
							. mysql_real_escape_string($maxscan)
							. "', max_promo='"
							. mysql_real_escape_string($promolimit)
							. "' WHERE `id`='"
							. mysql_real_escape_string($_SESSION['selector'])
							. "'";
						
						$result = mysql_query($sqlUpdateMerchant);
						if ($result == FALSE || $loyalty_result == FALSE)
						{
							$error = TRUE;
							$new_merchant_error = "1_Error in DB, please contact admin";
						}
						else
						{
							if ($is_activation == TRUE)
								INSERT_BO_USER($email_bo, $name, $_SESSION['selector']);
							
							if ($is_supermarchand == "1")
							{
								if (!category_set_marchandId($_SESSION['selector'], $res))
								{
									$error = TRUE;
									$new_merchant_error = "6_Error in DB, please contact admin";
								}
								/// update des logos des "fils" en DB
								update_merchant_picture($_SESSION['selector'], $logo);
							}
							if ($error == FALSE)
								$new_merchant_error = "Mise a jour du Marchand réussi avec succès!";
						}
					}
					
					//$sqlUpdateMarchand = "UPDATE $tbl_marchands"
				}
				else
				{
					$error = TRUE;
					$new_merchant_error = "2_Error in DB, please contact admin";
				}
			}
			////////////////////////////////////////////////////////////////////////////////////
			/// INSERT D'UN MARCHAND EN BDD
			else
			{
				$sqlGetCategory = "SELECT * FROM label WHERE `nom` = '"
						. mysql_real_escape_string($category)
						. "'";
						
				$result = mysql_query($sqlGetCategory);
					
				if ($categoryRow = mysql_fetch_array($result))
				{
					$res = -1;
					$supermarchand_id = -1;
					
					/// Attention un superMarchand cree un nouveau label, mais ne le prends pas.
					if ($is_supermarchand == "1")
					{
						$category = $categoryRow['id']; 
						$res = INSERT_LABEL($name, "franchise");
					}
					else 
					{	
						$category = $categoryRow['id'];
						
						/// Get the supermarchand_id
						if ($category)
						{
							$supermarchand_id = getSupermerchantId($category); 
						}
						
						if (isset($supermarchand_id))
							$logger->log('debug', 'commerciaux_register_marchand', "supermarchand_id=".$supermarchand_id, Logger::GRAN_MONTH);
					}
					
					$logger->log('debug', 'commerciaux_register_marchand', "supermarchand_id=".$supermarchand_id . " category=" . $category . " res=" . $res, Logger::GRAN_MONTH);
					
					if ($category && $supermarchand_id && $res)
					{
						$loyalty_result = TRUE;
						$is_activation = FALSE;
						
						/// Creation du marchand sur Loyalty
						if ($is_active == "1" && empty($merchantRow['application_id']))
						{
							$application_id = gen_uuid();
							$loyalty_result = loyalty_addApplication($name, $application_id);
							/// Ajout du compte backoffice user pour le marchands
							$is_activation = TRUE;
							
							$logger->log('debug', 'commerciaux_register_marchand', "Go for merchant registration::result=" . $loyalty_result, Logger::GRAN_MONTH);
						}
						
						/// Transfert d'image
						require_once('picture_transfer.php');
						
						/// Ajout d'un suffixe au marchand si le nom existe deja
						$suffixe = get_suffixe($name);
						$name .= $suffixe;
						
						$sqlInsertMerchant = "INSERT INTO $tbl_marchands SET contact='"
							 	. mysql_real_escape_string($contact)
								. "', name='"
								. mysql_real_escape_string($name)
								. "', phone='"
								. mysql_real_escape_string($phone)
								. "', address='"
								. mysql_real_escape_string($address)
								. "', city='"
								. mysql_real_escape_string($city)
								. "', zip_code='"
								. mysql_real_escape_string($zip_code)
								. "', longitude='"
								. mysql_real_escape_string($longitude)
								. "', latittude='"
								. mysql_real_escape_string($latitude)
								. "', site_internet='"
								. mysql_real_escape_string($website)
								. "', page_fb='"
								. mysql_real_escape_string($fb_page)
								. "', logo='"
								. mysql_real_escape_string($logo)
								. "', logo_mini='"
								. mysql_real_escape_string($logo_mini)
								. "', horaire='"
								. mysql_real_escape_string($horaires)
								. "', label_id='"
								. mysql_real_escape_string($category)
								. "', is_accueil_client='"
								. mysql_real_escape_string($is_accueil_client)
								. "', is_signalez_vous='"
								. mysql_real_escape_string($is_signalez_vous)
								. "', is_pin_marchand='"
								. mysql_real_escape_string($is_pin_marchand)
								. "', is_email_actif='"
								. mysql_real_escape_string($is_emailing)
								. "', is_active='"
								. mysql_real_escape_string($is_active)
								. "', is_supermarchand='"
								. mysql_real_escape_string($is_supermarchand)
								. "', email_backoffice='"
								. mysql_real_escape_string($email_bo)
								. "', pin_code='"
								. mysql_real_escape_string($pin_code)
								. "', supermarchand_id='"
								. mysql_real_escape_string($supermarchand_id)
								. "', max_scan='"
								. mysql_real_escape_string($maxscan)
								. "', max_promo='"
								. mysql_real_escape_string($promolimit)
								. "', application_id='"
								. mysql_real_escape_string($application_id)
								. "', date_inscription=CURDATE()";
								
// TEST ALEX
						if($_SESSION['login'] == "admin_pagesdor")
							$sqlInsertMerchant = $sqlInsertMerchant . ", country = 'Belgique'";
// END TEST ALEX
						
						$logger->log('debug', 'commerciaux_register_marchand', "New Merchant: query=" . $sqlInsertMerchant, Logger::GRAN_MONTH);
						
						$result = mysql_query($sqlInsertMerchant);
						if ($result == FALSE || $loyalty_result == FALSE)
						{
							$error = TRUE;
							$new_merchant_error = "3_Error in DB, please contact admin";
						}
						else
						{
							$UID = mysql_insert_id();
							
							/// Insertion d'une nouvelle ligne dans la table yfcommercial_have_merchant
							if ($_SESSION['role'] == "youfid_commerciaux")
								INSERT_COMMERCIAL_HAVE_MERCHANT($_SESSION['usr_id'], $UID, $name);
							
							/// Insertion d'un nouveau compte bo user
							if ($is_activation == TRUE)
									INSERT_BO_USER($email_bo, $name, $UID);
							
							if ($is_supermarchand == "1")
							{
								if (!category_set_marchandId($UID, $res))
								{
									$error = TRUE;
									$new_merchant_error = "6_Error in DB, please contact admin";
								}
							}
							if ($error == FALSE)
								$new_merchant_error = "Création du nouveau Marchand réussi avec succès!";
						}
					}
					else
					{
						$error = TRUE;
						$new_merchant_error = "5_Error in DB, please contact admin";
					}
				}
				else
				{
					$error = true;
					$new_merchant_error = "4_Error in DB, please contact admin";
				}
			}
					
		}
	}

	/// Cherche si le nom existe en db, si oui, renvois un suffixe
	function get_suffixe($merchant_name)
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `name`='"
			. mysql_real_escape_string($merchant_name)
			. "'";
			
		$result = mysql_query($query);
		if (!mysql_num_rows($result))
			return "";
		
		$query = "SELECT * FROM $tbl_marchands WHERE `name` LIKE '"
			. mysql_real_escape_string($merchant_name)
			. "%'";
			
		$result = mysql_query($query);
		return " " . strval(mysql_num_rows($result) + 1);
	}

	if(isset($_POST['name']) && !empty($_POST['name']))
		$_POST['name'] = "";
	/// Les switch ne sont transmis par post que si ils sont sur ON
	if (isset($logger))
		$logger->log('debug', 'commerciaux_register_marchand', "error=" . $new_merchant_error, Logger::GRAN_MONTH);
	
	$_SESSION['user_error'] = $new_merchant_error;
	
	header("location:../" . $_SESSION['selector_current_location']);
?>
