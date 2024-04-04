<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();	
 

	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');

	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'export_client', $message, Logger::GRAN_MONTH);
	}

	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	if (isset($logger))
		$logger->log('debug', 'table_merchant_data', "inFILE", Logger::GRAN_MONTH);

	$tbl_authentification = "authentification";
	$tbl_marchands = "marchand";
	$tbl_label = "label";
	
	$merchant_has_mbuser = "marchand_has_mobileuser";
	$tbl_mbuser = "mobileuser";

	doLog("///////////////////////////////////////////// START ////////////////////");

	$merchant_id = 0;
	if (isset($_SESSION['selector']) && $_SESSION['selector'] != 0)
		$merchant_id = $_SESSION['selector'];

	//doLog(intval($merchant_id));

	$result = array();
	if ($merchant_id > 0)
		$result = sort_data();
	else $result = sort_data_all();
	
	//////////////////////////// .CSV EXPORT //////////////////////////////////////////
	
	// Nom du fichier final
	$fileName = "clients-" . date("d") . "_" . date("m") . "_" .date("Y"). ".csv";
	// la variable qui va contenir les données CSV
	$outputCsv=utf8_encode("�a�!");
	$outputCsv="\xEF\xBB\xBF".$text;
	
	//$outputCsv = '';
	for ($i = 0; isset($result[$i]); $i += 1)
	{
        //$i++;
        $Row = $result[$i];
        // Si c'est la 1er boucle, on affiche le nom des champs pour avoir un titre pour chaque colonne
        if($i == 0)
        {
            foreach($Row as $clef => $valeur)
                $outputCsv .= trim($clef).';';

            $outputCsv = rtrim($outputCsv, ';');
            $outputCsv .= "\n";
        }

        // On parcours $Row et on ajout chaque valeur à cette ligne
        foreach($Row as $clef => $valeur)
            $outputCsv .= trim($valeur).';';

        // Suppression du ; qui traine à la fin
        $outputCsv = rtrim($outputCsv, ';');

        // Saut de ligne
        $outputCsv .= "\n";

    }

	//////////////////////////// .CSV EXPORT //////////////////////////////////////////	

	// Entêtes (headers) PHP qui vont bien pour la création d'un fichier Excel CSV
	header("Content-disposition: attachment; filename=".$fileName);
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: application/vnd.ms-excel\n");
	header("Pragma: no-cache");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
	header("Expires: 0");
	
	echo $outputCsv;
	exit();
	
	function sort_data_all()
	{
		global $tbl_mbuser, $merchant_id;
		
		$query = "SELECT * FROM $tbl_mbuser";
		
		$result = mysql_query($query);
		
		doLog("inSort_all_data");
		if ($result == FALSE)
			return FALSE;
		
		$temp_array = array();
		while ($row = mysql_fetch_array($result))
		{
			//doLog("User::id=" . $row['id']);
			$temp_user = array();
			
			/* ALEX */
			//$temp_user['id'] = $row['id'];
			$date = new DateTime($row['date_inscription']);
			$stringDate = $date->format('d.m.Y');
			$temp_user['Inscription'] = $stringDate;
			
			$temp_user['Nom'] = $row['nom'];
			$temp_user['Prenom'] = $row['prenom'];
			$temp_user['E-mail'] = $row['mail'];
			$temp_user['Nombre d\'utilisations'] = strval(get_all_nb_use($row));
			$temp_user['Commercant favori'] = get_favourite_merchant($row);
			$temp_user['Telephone'] = $row['phone'];
			$temp_user['Adresse'] = $row['address'];
			$temp_user['Code Postal'] = $row['zip'];
			$temp_user['Ville'] = $row['city'];
			$temp_user['Enfant - de 6ans'] = $row['have6ychild'] ? "Oui" : "Non";
			array_push($temp_array, $temp_user);
		}

		//print_r($temp_array);
		return $temp_array;
	}
	
	function get_favourite_merchant($user)
	{
		global $merchant_has_mbuser;
		
		$query = "SELECT * FROM $merchant_has_mbuser WHERE `mobileuser_id`='"
			. mysql_real_escape_string($user['id'])
			. "'";
			
		$result = mysql_query($query);
		if ($result == FALSE)
			return "";
		
		$nb_use = 0;
		$merchant_id = 0;
		
		while ($row = mysql_fetch_array($result))
		{
			if ($row['nb_use'] > $nb_use)
			{
				$nb_use = $row['nb_user'];
				$merchant_id = $row['marchand_id'];
			}
		}
		
		if ($merchant_id)
		{
			$merchant = get_merchant($merchant_id);
			return $merchant['name'];
		}
		
		return "";
	}
	
	/// Renvois le nombre d'utilisation totale
	function get_all_nb_use($user)
	{
		global $merchant_has_mbuser;
		
		$query = "SELECT * FROM $merchant_has_mbuser WHERE nb_use >0 AND `mobileuser_id`='"
			. mysql_real_escape_string($user['id'])
			. "'";
			
		$result = mysql_query($query);
		if ($result == FALSE)
			return FALSE;
		
		$nb_use = 0;
		while ($row = mysql_fetch_array($result))
			$nb_use += $row['nb_use'];
		
		return $nb_use;
	}
	
	function sort_data()
	{
		global $merchant_has_mbuser, $merchant_id;
		
		doLog("MerchantId::" . $merchant_id);

		/// Login loyalty
        //// LOGIN UPDATE ////
		/*if (($youfid_access = doLoyaltyLogin()) == FALSE)
			return FALSE;*/
        class YouFidAccess{
            var $wsAccess = '7e17880d34734a43b83848f76b1452b3';
        }
        $youfid_access = new YouFidAccess;
        //// END LOGIN UPDATE ////
		
		//$query = "SELECT * FROM $merchant_has_mbuser WHERE nb_use > 0 AND `marchand_id`='"
		//	. mysql_real_escape_string($merchant_id)
		//	. "'";
		
		$query = "SELECT `mobileuser`.* FROM `marchand_has_mobileuser`
				  INNER JOIN `mobileuser` ON `mobileuser`.`id` = `marchand_has_mobileuser`.`mobileuser_id`
				  WHERE `marchand_has_mobileuser`.`nb_use` > 0 AND `marchand_id`= '" . mysql_real_escape_string($merchant_id) . "'";
				  
		$result = mysql_query($query);
		
		if ($result == FALSE)
		{
			doLog("mobiuserRequest==FALSE::" . $query);
			return FALSE;
		}
		
		$merchant = get_merchant($merchant_id);
		if ($merchant == FALSE)
		{
			doLog("marchandRequest== FALSE");
			return FALSE;
		}
		
		doLog("query=::" . $query);
		
		$mbuser_array = array();
		while ($mb_user = mysql_fetch_array($result))
		{
			//doLog("inLoop");
			
			//$mb_user = get_mb_user($row['mobileuser_id']);
			
			//if ($mb_user)
			//{
			//doLog("mb_user::" . $mb_user['id']);
			
			$user = array();
			
			/* ALEX */
			//$user['id'] = strval($mb_user['id']);
			$date = new DateTime($mb_user['date_inscription']);
			$stringDate = $date->format('d.m.Y');
			$user['Inscription'] = $stringDate;
			
			$user['Nom'] = $mb_user['nom'];
			$user['Prenom'] = $mb_user['prenom'];
			$user['Points'] = get_nb_pts($mb_user, $youfid_access->wsAccess, $merchant);
			$user['Utilisations'] = strval($mb_user['nb_use']);
			$user['E-mail'] = $mb_user['mail'];
			$user['Telephone'] = $mb_user['phone'];
			$user['Adresse'] = $mb_user['address'];
			$user['Code Postal'] = $mb_user['zip'];
			$user['Ville'] = $mb_user['city'];
			$user['Enfant - de 6ans'] = $mb_user['have6ychild'] ? "Oui" : "Non";
			$distance = distance($merchant['latittude'], $merchant['longitude'], $mb_user['lattitude'], $mb_user['longitude']);
			$distance = number_format($distance, 3); 
			$user['distance'] = strval($distance) . " km";
			
			array_push($mbuser_array, $user);
			//}
		}
		
		return $mbuser_array;
	}

	function get_merchant($merchant_id)
	{
		global $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		if ($result != FALSE && $row = mysql_fetch_array($result))
			return $row; 
		return FALSE;
	}

	function get_mb_user($user_id)
	{
		global $tbl_mbuser;
		
		$query = "SELECT * FROM $tbl_mbuser WHERE nb_use > 0 AND`id`='"
			. mysql_real_escape_string($user_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result != FALSE && $row = mysql_fetch_array($result))
			return $row; 
		return FALSE;
	}
	
	function get_nb_pts($user, $wsAccess, $merchant)
	{
		$result = doMobiuserAppsRequest($user, $wsAccess);
		
		$nb_pts = strval(0);
		for ($index = 0; isset($result[$index]); $index += 1)
		{
			if ($merchant['application_id'] == $result[$index]->application->publicId)
				$nb_pts = strval($result[$index]->totalPoints);
		}
		
		return strval($nb_pts);
	}
	
	/// Recupere sur loyalty les informations d'une application marchands. Retourne FALSE en cas d'erreur
	function doMobiuserAppsRequest($user, $wsAccess)
	{
		// Urls
		global $url_loyalty;
		$service_base_url = $url_loyalty . "services/";
		$get_application_service = "mobileuser/mobiuserapps";
		
		$req_get_application = array(
			"wsAccessPublicKey" => $wsAccess->wsAccessPublicKey,
			"wsAccessToken" => $wsAccess->wsAccessToken,
			"mobileUserPublicId" => $user['public_id']
		);
		
		$req_get_application_json = json_encode($req_get_application);
		
		$res_get_application_json = postRequest($service_base_url . $get_application_service, $req_get_application_json);
		
		//doLog("Loyalty RESPONSE=" . $res_get_application_json);
		$res_get_application = json_decode($res_get_application_json);
		
		//echo "<br/> get_application failure <br/> response=" . $res_get_application_json;
		
		if(!empty($res_get_application) && !empty($res_get_application->error) && $res_get_application->error->code == 0)
		{		
			return $res_get_application->mobileUserApplications;
		}
		
		return (array());
	}

	/// Fonction de login a loyalty
	function doLoyaltyLogin()
	{
		//global $logger;
		
		global $url_loyalty;
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
?>
