
<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	require_once("db_functions.php");
	require_once 'push_message.php';
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	require_once '../mail/lib/swift_required.php';
	echo "test 3";
	require_once("../dev/service/dbLogInfo.php");
	$tbl_name = "marchand_has_mobileuser";
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	echo "test 1";
	$marchand_id= $_SESSION['selector'];
	if ($marchand_id == 'NEW')
		$marchand_id = 0;
	$AllClient = "Select id from mobileuser";
	$result = mysql_query($AllClient);
	$i = 0;
	echo "test 1";
	while ($row = mysql_fetch_array($result)) {
		$testUse = "Select * from marchand_has_mobileuser WHERE mobileuser_id ='" . $row['id']  . "' && nb_use > 0";
		$resultUse = mysql_query($testUse);
		if (mysql_num_rows($resultUse)) {
			$userArray[$i] = $row['id'];
			$i += 1;
		}
	}
	$arrayRem['titre'] = $_POST['titre'];
	$arrayRem['contenu'] = $_POST['contenu'];
	$arrayRem['date_debut'] = $_POST['date_debut'];
	$arrayRem['date_fin'] = $_POST['date_fin'];
	$arrayRem['scanloc'] = 0;
	$arrayRem['localisation'] = $_POST['localisation'];
	$arrayRem['time'] = 0;
	$arrayRem['combien'] = $_POST['combien'];
	$arrayRem['chrono'] = $_POST['chrono'];
	$arrayRem['domaine'] = 0;
	$arrayRem['domaines'] = $_POST['domaines'];
	
	
	if ($marchand_id != 0)
	 {
	 	
	 	$i = 0;
	 	while ($userArray[$i]) {
			$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
									. mysql_real_escape_string($userArray[$i])
									. "' && `marchand_id` = '" 
									. mysql_real_escape_string($marchand_id) 
									. "'";
			$result2 = mysql_query($sqlGetCustomer);
			$rowNb = mysql_num_rows($result2);
			if ($rowNb < 1) {
				unset($userArray[$i]);
			}
			$i += 1;
		 }
		$userArray = array_values($userArray);
	}
	
	
	
		if (isset($_POST['scanloc'])) {
			$arrayRem['scanloc'] = 1;
			$sqlLoc = "Select id from marchand where zip_code = '"
				. mysql_real_escape_string($_POST['localisation'])
				. "'";
			$resultmarchand = mysql_query($sqlLoc);
			$a = 0;
			while ($rowzip = mysql_fetch_array($resultmarchand)) {
				$marchandOK[$a] = $rowzip['id'];
				$a += 1;
			}
			$i = 0;
			while ($userArray[$i]) {
				$userOK = FALSE;
				$j = 0;
				while ($marchandOK[$j]) {
					$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
									. mysql_real_escape_string($userArray[$i])
									. "' && `marchand_id` = '" 
									. mysql_real_escape_string($marchandOK[$j]) 
									. "'";
					$result2 = mysql_query($sqlGetCustomer);
					$rowNb = mysql_num_rows($result2);
					if ($rowNb>= 1) {
						$userOK = TRUE;
						break;
					}
					$j += 1;
				}
			if ($userOK == FALSE) {
				unset($userArray[$i]);
			}
				$i += 1;
			}
			
			$userArray = array_values($userArray);
		}
	
				
	if (isset($_POST['time'])) {
		$arrayRem['time'] = 1;
			$mult = 1;
			if ($_POST['chrono'] == "Semaines")
				$mult = 7;
			else if ($_POST['chrono'] == "Mois") {
				$mult = 30;
			}
			elseif ($_POST['chrono'] == "Années") {
				$mult = 365;
			}
			$last = intval($_POST['combien']);
			$maxdays = $last * $mult;
			$i = 0;
			while ($userArray[$i]) {
				$sqlGetCustomer = "SELECT * FROM authentification WHERE `mobileuser_id` = '"
									. mysql_real_escape_string($userArray[$i])
									. "' AND `marchand_id` = '"
									. mysql_real_escape_string($marchand_id)
									. "' AND TO_DAYS(NOW()) - TO_DAYS(`authent_date`) <= "
									. mysql_real_escape_string($maxdays);
									
					$result2 = mysql_query($sqlGetCustomer);
					$rowNb = mysql_num_rows($result2);
					if ($rowNb < 1) {
						unset($userArray[$i]);
					}
				$i += 1;
			}
			$userArray = array_values($userArray);
	}
	
	if (isset($_POST['domaine'])) {
		$arrayRem['domaine'] = 1;
			$getIdDomaine = "Select * from label where nom ='"
							. mysql_real_escape_string($_POST['domaines']) 
							. "'";
			$resultDom = mysql_query($getIdDomaine);		
			$rowdom = mysql_fetch_array($resultDom);
		
			$sqlLoc = "Select id from marchand where label_id = '"
				. mysql_real_escape_string($rowdom['id'])
				. "'";
			$resultmarchand = mysql_query($sqlLoc);
			$a = 0;
			while ($rowzip = mysql_fetch_array($resultmarchand)) {
				$marchandOK2[$a] = $rowzip['id'];
				$a += 1;
			}
			$i = 0;
			while ($userArray[$i]) {
				$userOK = FALSE;
				$j = 0;
				while ($marchandOK2[$j]) {
					$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
									. mysql_real_escape_string($userArray[$i])
									. "' && `marchand_id` = '" 
									. mysql_real_escape_string($marchandOK2[$j]) 
									. "'";
					$result2 = mysql_query($sqlGetCustomer);
					$rowNb = mysql_num_rows($result2);
					if ($rowNb>= 1) {
						$userOK = TRUE;
						break;
					}
					$j += 1;
				}
			if ($userOK == FALSE) {
				unset($userArray[$i]);
			}
				$i += 1;
			}
			$userArray = array_values($userArray);
		}
		
		$_SESSION['remember'] = $arrayRem;
		$_SESSION['nb_client'] = $userArray;
		if (isset($_POST['submit1'])) {
			
			header("location:../youfid_master_promos.php");
		}
		elseif (isset($_POST['submit2']) && count($userArray)) {
			$_SESSION['sent'] = 1;
			$i = 0;
			$valid = '0';
			if ($_SESSION['role'] == "youfid_master") {
				$valid = '1';
			}
			mysql_query("SET NAMES utf8");
			$insertPromo = " Insert into message SET marchand_id='"
					. mysql_real_escape_string($marchand_id)
					. "', type='"
					. mysql_real_escape_string("promo")
					. "', message='"
					. mysql_real_escape_string($_POST['titre'])
					. "', detail='"
					. mysql_real_escape_string($_POST['contenu'])
					. "', start_date='"
					. mysql_real_escape_string(sql_date_format($_POST['date_debut']))
					. "', finish_date='"
					. mysql_real_escape_string(sql_date_format($_POST['date_fin']))
					. "', is_validated='"
					. mysql_real_escape_string($valid)
					. "'";
			 mysql_query($insertPromo);
			 $getPromoId = "SELECT * FROM message WHERE marchand_id='"
					. mysql_real_escape_string($marchand_id)
					. "'AND type='"
					. mysql_real_escape_string("promo")
					. "'AND message='"
					. mysql_real_escape_string($_POST['titre'])
					. "'AND detail='"
					. mysql_real_escape_string($_POST['contenu'])
					. "'AND start_date='"
					. mysql_real_escape_string(sql_date_format($_POST['date_debut']))
					. "'AND finish_date='"
					. mysql_real_escape_string(sql_date_format($_POST['date_fin']))
					. "'";
			$result = mysql_query($getPromoId);
			$rowpro = mysql_fetch_array($result);
			$idPromo = $rowpro['id'];
			while ($userArray[$i]) {
				$insertPro = "  INSERT into message_has_mobileuser SET message_id='"
				. mysql_real_escape_string($idPromo)
				. "', mobileuser_id='"
				. mysql_real_escape_string($userArray[$i])
				. "', date_creation=NOW()";
				$result = mysql_query($insertPro);
				//push
				
				$i += 1;
			}
			
		$today =  date('Y-m-d');
		$getMarchandId = "Select * from message WHERE id='" . $idPromo . "' && start_date <= '$today' && finish_date >= '$today'";
		$marchandResult = mysql_query($getMarchandId);
		
		
		if ($_SESSION['role'] == "youfid_master" && mysql_num_rows($marchandResult)) {
			$rowTab = array();
			$bigtab = array();
			$rowTab['id_msg'] = $idPromo;
			$rowTab['id_marchand'] = $marchand_id;
			$rowTab['id_users'] = $userArray;
			array_push($bigtab, $rowTab);
			send_push_msg($bigtab);
		}
		header("location:../youfid_master_promos.php");
		
		}
		elseif (isset($_POST['email'])) {
			$valid = '0';
		
			mysql_query("SET NAMES utf8");
			$insertEmail = "INSERT into message SET marchand_id='"
					. mysql_real_escape_string($marchand_id)
					. "', type='"
					. mysql_real_escape_string("email")
					. "', message='"
					. mysql_real_escape_string($_POST['objet'])
					. "', detail='"
					. mysql_real_escape_string($_POST['mail'])
					. "', is_validated='"
					. mysql_real_escape_string($valid)
					. "'";
			 mysql_query($insertEmail);
			 $getPromoId = "SELECT * FROM message WHERE marchand_id='"
					. mysql_real_escape_string($marchand_id)
					. "'AND type='"
					. mysql_real_escape_string("email")
					. "'AND message='"
					. mysql_real_escape_string($_POST['objet'])
					. "'AND detail='"
					. mysql_real_escape_string($_POST['mail'])
					. "'AND is_validated='"
					. mysql_real_escape_string($valid)
					. "'";
			$result = mysql_query($getPromoId);
			$rowpro = mysql_fetch_array($result);
			$idPromo = $rowpro['id'];
			$i = 0;
			while ($_SESSION['nb_client'][$i]) {
				$insertPro = "  INSERT into message_has_mobileuser SET message_id='"
				. mysql_real_escape_string($idPromo)
				. "', mobileuser_id='"
				. mysql_real_escape_string($userArray[$i])
				. "', date_creation=NOW()";
				$result = mysql_query($insertPro);
				$i += 1;
				
			}
			if ($_SESSION['role'] == "youfid_master") {
						// MAILL
				$getMail = "SELECT * FROM message WHERE id='" . $idPromo . "'";
				$result = mysql_query($getMail);
				$rowMail = mysql_fetch_array($result);
		
		

				$transport = Swift_SmtpTransport::newInstance('localhost', 25);
				$mailer = Swift_Mailer::newInstance($transport);
		
				$getUsers = "SELECT * FROM message_has_mobileuser  WHERE message_id='" . $idPromo . "'";
				$resultUser = mysql_query($getUsers);

				while ($rowUser = mysql_fetch_array($resultUser)) {
					$getUser2 = "Select * from mobileuser where id = '" . $rowUser['mobileuser_id'] . "'";
					$resultUser2 = mysql_query($getUser2);
					$rowUser2 = mysql_fetch_array($resultUser2);
			
					$message = Swift_Message::newInstance($rowMail['message'])
 					 ->setFrom(array(' admin@youfid.fr'))
 					 ->setTo(array($rowUser2['mail']))
 					 ->setBody($rowMail['detail']);

					$result = $mailer->send($message);
		
		}
		
		$updateCadeau = "UPDATE message SET is_validated='1' WHERE id='" . $idPromo . "'";
		$resultUp = mysql_query($updateCadeau);
	}
						
		header("location:../youfid_master_promos.php");
	}
		elseif (isset($_POST['email_before'])) {
			header("location:../email_promo.php");
		}
		header("location:../youfid_master_promos.php");

?>
