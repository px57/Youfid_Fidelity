<?php

	/////////////////////////////////////////////////////////////////////////////////
	//// Contient des fonction pour ajouter, supprimer, update des objets en DB
	
	//require_once("Logger.class.php");
//	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Ressource/email_content.php");
/*	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	if (isset($logger))
		$logger->log('debug', 'db_function', "in file", Logger::GRAN_MONTH);
*/	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	/*require_once("../dev/service/dbLogInfo.php");
	require_once("../dev/service/utils.php");*/
	
	$tbl_marchands = "marchand";
	$tbl_label = "label";
	$tbl_user = "mobileuser";
	$tbl_bo_user = "backoffice_usr";
	$tbl_chm = "yfcommercial_have_merchant";
	
	$merchant_has_mbuser = "marchand_has_mobileuser";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	function get_merchant_2($merchant_id)
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		if ($result == FALSE || !mysql_num_rows($result))
			return FALSE;
		
		// Return Merchant
		return mysql_fetch_array($result);
	}
	
	function check_sm_subscription($super_merchant_id, $user_id)
	{
		global $merchant_has_mbuser;
		
		$query = "SELECT * FROM $merchant_has_mbuser WHERE `marchand_id`='"
			. mysql_real_escape_string($super_merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "'";
			
		$result = mysql_query($query);
			
		if (mysql_num_rows($result))
			return TRUE;
		
		return FALSE;
	}
	
	/// Inscrit un user a un super_marchand si il ne l'est pas.
	function subscribe_user_to_sm($merchant_id, $user_id)
	{
		global $merchant_has_mbuser;
		
	/*	$query = "SELECT * FROM $merchant_has_mbuser WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE || !mysql_num_rows($result))
			return 1;
		
		$row = mysql_fetch_array($result);*/
		$merchant = get_merchant_2($merchant_id);
		if (!$merchant || $merchant['is_supermarchand'] == '1')
			return 2;
		if (empty($merchant['supermarchand_id']) || $merchant['supermarchand_id'] == "-1")
			return 3;
		
		/// Verification de l'inscription
		if (check_sm_subscription($merchant['supermarchand_id'], $user_id))
			return 4;
		
		$query = "INSERT INTO $merchant_has_mbuser SET `marchand_id`='"
			. mysql_real_escape_string($merchant['supermarchand_id'])
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "', `nb_use`='0'";
			
		$result = mysql_query($query);
		return 0;
	}
	
	/// Verifie si le marchand dispose des droits necessaire a l'update de son logo
	function can_update_logo($label_id)
	{
		global /*$logger,*/ $tbl_label;
		
		if ($label_id == -1)
			return TRUE;
		
		$sqlSelectLabel = "SELECT * FROM $tbl_label WHERE `id`='"
			. mysql_real_escape_string($label_id)
			. "'";
			
		$result = mysql_query($sqlSelectLabel);
		
		if ($row = mysql_fetch_array($result))
		{
			if (strtolower($row['type']) == "categorie")
				return TRUE;
		}
		return FALSE;
	}
	
	/// Ajout d'un compte back office user pour un marchand et envois le mdp par email
	function INSERT_BO_USER($email_bo, $merchant_name, $merchant_id)
	{
		global $tbl_bo_user/*, $logger*/;
		
		$merchant_name = str_replace(" ", "_", $merchant_name);
		$password = generatePassword();
		
		$sqlInsert = "INSERT INTO $tbl_bo_user SET `id_role`='"
			. mysql_real_escape_string("4")
			. "', `login`='"
			. mysql_real_escape_string($merchant_name)
			. "', `password`='"
			. mysql_real_escape_string($password)
			. "', `id_marchand`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		//$logger->log('debug', 'db_function', "INSERT_BO_USER::query::" . $sqlInsert, Logger::GRAN_MONTH);
		
		$result = mysql_query($sqlInsert);
		
		/// Envois du password par email
		if ($result)
		{
			/////////////////////////////////
			/// Email definition
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$headers .=	'Content-Transfer-Encoding: 8bit' . "\r\n";
			$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
			
			$bo_email_content = "Bonjour,<br/><br/>Votre compte marchand Youfid vient d'etre activé. Veuillez trouvez ici vos identifiants:<br/> login : "
			. $merchant_name . "<br/>password :" . $password . "<br/>Vous etes libre de changer votre mot de passe en vous rendant a l'addresse suivante: <a href='http://backoffice.youfid.fr/change_password.php'> http://backoffice.youfid.fr/change_password.php </a>";
			$bo_email_content .= "<br/><br/>N'hésitez pas a nous contacter pour plus de renseignements.<br/><br/>Merci,<br/>L'équipe YouFID</br>";
			
			//mail($email_bo, 'Votre mot de passe YouFid - Back Office', utf8_decode($bo_email_content), $headers) or die ("Couldn't send mail!" );
			mail_youfid($email_bo, 'Votre mot de passe YouFid - Back Office', $bo_email_content);
		}
	}
	
	/// Renvoi un id si success, ou FALSE en cas d'erreur. Si le label est deja present et de meme $type, renvoie l'id de ce dernier
	function INSERT_LABEL($name, $type)
	{
		global $tbl_label/*, $logger*/;
		
		$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `nom` ='"
			. mysql_real_escape_string($name)
			. "'";
		
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::INSERT_LABEL::queryGetLabel=" . $sqlGetLabel, Logger::GRAN_MONTH);
		
		$result = mysql_query($sqlGetLabel);
		
		if ($result == FALSE)
			return FALSE;
		
		if ($row = mysql_fetch_array($result))
		{
			
			/*$res = array();
			$res['id'] = $row['id'];
			$res['type'] = $row['type'];*/ 
			
			return $row['id'];
			//return $res;
		}
		
		$sqlInsertLabel = "INSERT INTO $tbl_label SET `nom`='"
			. mysql_real_escape_string($name)
			. "', `type`='"
			. mysql_real_escape_string($type)
			. "'";
			
		$result = mysql_query($sqlInsertLabel);
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::INSERT_LABEL::query=" . $sqlInsertLabel, Logger::GRAN_MONTH);
		
		if ($result != FALSE)
		{
			$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `nom`='"
			. mysql_real_escape_string($name)
			. "' AND `type`='"
			. mysql_real_escape_string($type)
			. "'";
			
			$result = mysql_query($sqlGetLabel);
			//if (isset($logger))
			//	$logger->log('debug', 'db_function', "::INSERT_LABEL::query=" . $sqlGetLabel, Logger::GRAN_MONTH);
			
			if ($row = mysql_fetch_array($result))
			{
				/*$res = array();
				$res['id'] = $row['id'];
				$res['type'] = $row['type'];
				return $res;*/
				
				return $row['id'];
			}
			return FALSE;
		}
		return FALSE;
	}
	
	/**
	 * 	getSupermerchantId(Int)
	 *  Retourne le SuperMerchant_id associé a une franchise
	 * 
	 *  @param Int $category_id id de la categorie du marchand
	 */
	function getSupermerchantId($category_id)
	{
		global /*$logger,*/ $tbl_marchands, $tbl_label;
		
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::getSupermarchandID::category_id=" . $category_id, Logger::GRAN_MONTH);
		
		$sqlGetCatgory = "SELECT * FROM $tbl_label WHERE `id`='"
			. mysql_real_escape_string($category_id)
			. "'";
			
		$result = mysql_query($sqlGetCatgory);
		
		if ($result == FALSE)
			return FALSE;
		
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::getSupermarchandID::TEST", Logger::GRAN_MONTH);
		
		if ($category_row = mysql_fetch_array($result))
		{
			if (!isset($category_row['type']) || empty($category_row['type']) /*|| $category_row['type'] != "franchise"*/)
				return FALSE;
		
			/*$logger->log('debug', 'db_function', "::getSupermarchandID::TEST", Logger::GRAN_MONTH);
				
			$sqlGetMerchant = "SELECT * FROM $tbl_marchands WHERE `name` = '"
				. mysql_real_escape_string($category_row['nom'])
				. "'";
				
			$result = mysql_query($sqlGetMerchant);*/
			
			/*if ($row = mysql_fetch_array($result))
				return $row['id'];*/
				
			/*if($category_row['supermarchand_id'] == -1)
				return FALSE;*/
				
			return $category_row['supermarchand_id'];
		}
		return FALSE;
	}
	
	function delete_marchand($merchant_id)
	{
		global /*$logger,*/ $tbl_marchands, $tbl_label, $tbl_bo_user;
		
		/// Get les infos du marchands
		$sqlGetMerchant = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($sqlGetMerchant);
		
		if (!($merchant = mysql_fetch_array($result)))
			return FALSE;
		
		/// If super-merchant: On supprime les marchands "fils" et label
		if ($merchant['is_supermarchand'] == "1")
		{
			/// DELETE merchants
			$sqlDeleteMerchants = "DELETE FROM $tbl_marchands WHERE `supermarchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "'";
				
			$result = mysql_query($sqlDeleteMerchants);
			
			/// DELETE label
			$sqlDeleteLabel = "DELETE FROM $tbl_label WHERE `supermarchand_id`='"
				. mysql_real_escape_string($merchant_id)
				. "'";
				
			$result = mysql_query($sqlDeleteLabel);
		}
		
		/// DELETE back_office access
		$sqlDeleteBoAccess = "DELETE FROM $tbl_bo_user WHERE `id_marchand`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($sqlDeleteBoAccess);
		
		$sqlDeleteMerchant = "DELETE FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($sqlDeleteMerchant);
		
		if ($result)
			return TRUE;
		
		return FALSE;
	}
	
	function update_merchant_picture($super_merchant_id, $logo)
	{
		global /*$logger,*/ $tbl_marchands;
		
		/*$sqlGetSuperMarchand = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($super_merchant_id)
			. "'";
			
		$result = mysql_query($sqlGetSuperMarchand);
		
		/// Logo dont need to be updated
		if (($row = mysql_fetch_array($result)) && ($logo == $row['logo']))
		{
			if (isset($logger))
				$logger->log('debug', 'db_function', "::update_merchant_picture::logo is the same", Logger::GRAN_MONTH);
			return TRUE;
		}*/
		
		$sqlUpdateMerchants = "UPDATE $tbl_marchands SET `logo`='"
			. mysql_real_escape_string($logo)
			. "' WHERE `supermarchand_id`='"
			. mysql_real_escape_string($super_merchant_id)
			. "'";
		
		$result = mysql_query($sqlUpdateMerchants);
		return $result;
	}
	
	/// Ajoute une ligne a la table commercial_have_merchant
	function INSERT_COMMERCIAL_HAVE_MERCHANT($user_id, $merchant_id, $merchant_name)
	{
		global $tbl_chm/*, $logger*/;
		
		$query = "INSERT INTO $tbl_chm SET `user_id`='"
			. mysql_real_escape_string($user_id)
			. "', `merchant_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
		
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::INSERT_COMMERCIAL_HAVE_MERCHANT::query=" . $query, Logger::GRAN_MONTH);
		
		$result = mysql_query($query);
		
		if ($result != FALSE)
		{
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$headers .=	'Content-Transfer-Encoding: 8bit' . "\r\n";
			$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
			
			$mail_content = $_SESSION['login'] . " a ajouté " . $merchant_name . " au back-office, ce dernier est par conséquent en attente de validation.";
			//mail('admin@youfid.fr', '[Back-office] Marchand en attente de validation', utf8_decode($mail_content), $headers);
			mail_youfid('admin@youfid.fr', '[Back-office] Marchand en attente de validation', $mail_content);
		}
		return $result;
	}
	
	// MAJ : $application ID est en fait l'id du marchand
	function category_set_marchandId($application_id, $label_id)
	{
		global /*$logger,*/ $tbl_marchands, $tbl_label;
		
		//if (isset($logger))
		//	$logger->log('debug', 'db_function', "::category_set_marchandid::application_id=" . $application_id . " :: label_id=" . $label_id, Logger::GRAN_MONTH);
		
		$sqlGetMerchant = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($application_id)
			. "'";
			
		$result = mysql_query($sqlGetMerchant);
		
		if ($row = mysql_fetch_array($result))
		{
			/// Check if label exist
			$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `id`='"
				. mysql_real_escape_string($label_id)
				. "'";
			
			$result = mysql_query($result);
			
			if ($rowLabel = mysql_fetch_array($result))
			{
				if ($rowLabel['supermarchand_id'] == $row['id'])
					return TRUE;
				else
					return FALSE;
			}
			
			$sqlUpdateLabel = "UPDATE $tbl_label SET `supermarchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' WHERE `id`='"
				. mysql_real_escape_string($label_id)
				. "'";
				
			$result = mysql_query($sqlUpdateLabel);
			if ($result != FALSE)
				return TRUE;
		}
		return FALSE;
	}
?>
