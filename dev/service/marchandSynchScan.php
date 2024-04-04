<?php

	require_once 'utils.php';
	require_once('dbLogInfo.php');

	////////////////////////////////////////
	// DEBUG MODE
	// 1 = echo
    $debugmod = 0;

	function doLog($message)
	{
		global $debugmod;

		if($debugmod == 1)
			echo $message;
	}

	////////////////////////////////////////
	// Error properties
	$status = "ok";
	$errorMsg = "";

	////////////////////////////////////////
	// DataBase connection
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	//////////////
	// INPUT
	$json = file_get_contents('php://input');
	doLog("Request=" . $json);
	$jsonArray = json_decode($json);

	//////////////
	// FUNCTIONS

	function ajoutPts($rowMarchand, $public_id, $amount)
    {
       global $url_loyalty;
       $loginResult = '7e17880d34734a43b83848f76b1452b3';
       $result_accueil = FALSE;

       if($rowMarchand['is_accueil_client'] == '1'){
           $accueil_json = '{
                      "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                      "wsAccessToken" : "' . $loginResult . '",
                      "mobileUserPublicId" : "'. $public_id  . '",
                      "applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
                      "points" : "' . $rowMarchand['points_for_accueil']  . '"
                      }';
           $accueil_url = $url_loyalty . "services/transaction/addpoints";
           $result_accueil = postRequest($accueil_url, $accueil_json);
       }
       else if (isset($amount)){
           if($amount <= 4000)
           {
           	 $transaction_url = $url_loyalty . "services/transaction";
             $transaction_json = '{
                       "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                       "wsAccessToken" : "' . $loginResult . '",
                       "mobileUserPublicId" : "'. $public_id  . '",
                       "applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
                       "amount" : "' . $amount  . '"
                       }';
             $result_accueil = postRequest($transaction_url, $transaction_json);
           }
       }
       else{
           $toadd = $rowMarchand['points_for_accueil'];
           if ($toadd == 0)
               $toadd = 5;
           $accueil_json = '{
                      "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                      "wsAccessToken" : "' . $loginResult . '",
                      "mobileUserPublicId" : "'. $public_id  . '",
                      "applicationPublicId" : "' .  $rowMarchand['application_id'] . '",
                      "points" : ' . $toadd  . '
                      }';
           $accueil_url = $url_loyalty . "services/transaction/addpoints";
           $result_accueil = postRequest($accueil_url, $accueil_json);
       }

       return $result_accueil;

    }

	//////////////
	// PROCESSING
	$scan_nb = 0;
	foreach($jsonArray as $scan)
	{
		$scan_nb ++;
		$merchant_id = $scan->merchant_id;
		$amount = $scan->amount;

		$usr_id = false;
		$qr_code = false;
		$mail = false;
		$tx_date = "NOW()";
		if(isset($scan->usr_id))$usr_id = $scan->usr_id;
		if(isset($scan->qr_code))$qr_code = $scan->qr_code;
		if(isset($scan->mail))$mail = $scan->mail;
		if(isset($scan->date))
			$tx_date = "'" . $scan->date . "'";

	//////////////
	// 1. Checking if merchant registered on system, is active and if positive amount

		$sqlGetMerchant = "SELECT * FROM marchand WHERE `id` = '"
                . mysql_real_escape_string($merchant_id)
                . "'";
        $resultMerchant = mysql_query($sqlGetMerchant);
        $merchant = mysql_fetch_array($resultMerchant);

		if($amount >= 0 && isset($merchant) && ($merchant['is_active']== 1 || $merchant['id']==192))
		{

	//////////////
	// 2. Checking if user already registered on system

			$user_id = false;
			$public_id = false;

	//////////////
	// 2.1 If user_id provided (warning if not in system)

			if($usr_id)
			{
				$sqlGetUser = "SELECT * FROM mobileuser WHERE `id` = '"
                . mysql_real_escape_string($usr_id)
                . "'";
		        $resultUser = mysql_query($sqlGetUser);

				if($user = mysql_fetch_array($resultUser))
				{
					$user_id = $user['id'];
					$public_id = $user['public_id'];
				}

				else
				{
					$status = "warning";
					$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": User " . $scan->usr_id . " non existant (seul le user_id a ete fourni pour ce scan chez le marchand "
					. $merchant['name'] . ")";
				}
			}

	//////////////
	// 2.2 If qr_code only provided (register if not in system)

			else if($qr_code && !$mail)
			{
				$sqlGetUser = "SELECT * FROM mobileuser WHERE `qr_code` = '"
	                . mysql_real_escape_string($qr_code)
	                . "'";
		        $resultUser = mysql_query($sqlGetUser);

				if($user = mysql_fetch_array($resultUser))
				{
					$user_id = $user['id'];
					$public_id = $user['public_id'];
				}

				else
				{
					$public_id = gen_uuid();

					$sqlCarte = "INSERT INTO mobileuser SET `public_id`='"
						. mysql_real_escape_string($public_id)
						."', `status`='"
					 	. mysql_real_escape_string(2)
						. "', `qr_code`='"
						. mysql_real_escape_string($qr_code)
						. "', `first_merchant`='"
						. mysql_real_escape_string($merchant['name'])
						. "', `date_inscription`="
						. $tx_date;

					$result=mysql_query($sqlCarte);
					$user_id = mysql_insert_id();

					if ($result == FALSE)
					{
						$status = "warning";
						$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": Erreur lors du processus d'inscription du QR " . $qr_code  . " chez le marchand " . $merchant['name'];
					}

		            $reg_loyalty = '{
							"wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
							"wsAccessToken" : "' . '7e17880d34734a43b83848f76b1452b3' . '",
							"mobileUserPublicId" : "'. $public_id  . '",
							"applicationPublicId" : "' .  $merchant['application_id'] . '",
							"points" : 0
					}';

					$reg_url = $url_loyalty . "services/mobileuser";
					$resultReg =  postRequest($reg_url, $reg_loyalty);
				}
			}

	//////////////
	// 3. JNPMC

			if ($mail && $qr_code)
			{
				$sqlEmail = "SELECT * FROM mobileuser WHERE `mail` = '"
					. mysql_real_escape_string($mail)
					. "'";
				$result = mysql_query($sqlEmail);

				$sqlVerifQR = "SELECT * FROM mobileuser WHERE `qr_code` = '"
						. mysql_real_escape_string($qr_code)
						. "'";
				$result2 = mysql_query($sqlVerifQR);

				if ($result == FALSE || $result2 == FALSE)
				{
					$status = "warning";
					$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": Erreur de base de donnee lors du processus de changement de carte";
				}
				else
				{
					if (mysql_num_rows($result2))
					{
						$status = "warning";
						$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": Le QR code " . $qr_code . " existe deja en base de donnee et ne peut donc pas etre utilise pour y associer le mail " . $mail . " dans le cadre du processus de changement de carte chez le marchand " . $merchant['name'];
					}
					else if (mysql_num_rows($result))
					{
						$user = mysql_fetch_array($result);
						$user_id = $user['id'];
						$public_id = $user['public_id'];

						mail_youfid($mail, "Oubli de carte YouFid", "Vous avez récemment oublié votre carte YouFid et avez utilisé une carte de substitution. Si vous souhaitez désormais utiliser cette dernière en lieu et place de votre ancienne carte, merci de cliquer sur le lien suivant : http://www.youfid.fr/membres/carte/".$user['validation']."/".$qr_code."/", $user['prenom'] . " " . $user['nom'], 'youfid', 'oublidecarte2');
					}
					else
					{
						$status = "warning";
						$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": L'e-mail ". $mail ." n'est pas en base de donnee et il ne peut donc y etre associe le QR code "
						. $qr_code . " dans le cadre du processus de changement de carte chez le marchand " . $merchant['name'];
					}
				}
			}

	//////////////
	// 4. Proceeding to checkScan if no error till there
			if($user_id != false && $status != "warning")
			{
				$updateNbUse = "UPDATE mobileuser SET nb_use=nb_use+1 WHERE `id` = '"
						. mysql_real_escape_string($user_id)
						. "'";
				$resultUpdateNbUse = mysql_query($updateNbUse);

				$sqlGetCustomer = "SELECT * FROM marchand_has_mobileuser WHERE `mobileuser_id` = '"
					. mysql_real_escape_string($user_id)
					. "' && `marchand_id` = '"
					. mysql_real_escape_string($merchant_id)
					. "'";
				$resultGetCustomer = mysql_query($sqlGetCustomer);

				$rowNb = mysql_num_rows($resultGetCustomer);

	//////////////
	// 4.0.1 Checking if maxscan not reached for this user on this merchant

				$nbScan = "SELECT 'transaction_date' FROM `transaction` WHERE"
				. " UNIX_TIMESTAMP(transaction_date) BETWEEN UNIX_TIMESTAMP(CURDATE())"
				. " AND UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL + 86399 SECOND))"
				. " AND `mobileuser_id` = '"
				. mysql_real_escape_string($user_id)
				. "' AND `marchand_id` = '"
				. mysql_real_escape_string($merchant_id)
				. "' AND `value` > 0";

				$resultScan = mysql_query($nbScan);
				$totalScan = mysql_num_rows($resultScan);

				$maxScan = intval($merchant['max_scan']);

				if ($maxScan == 0)
					$maxScan = 1;
				if ($totalScan >= $maxScan){
					$status = "warning";
					$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":Nombre maximum de scans atteint pour l'utilisateur " . $user['id'] . ": " . $user['prenom']
					. " " . $user['nom'] . " chez le marchand " . $merchant['name'];
				}

	//////////////
	// 4.0.2 Checking if time of transaction is valid

				else if(isset($scan->date) && (strtotime($scan->date) > time()))
				{
					$status = "warning";
					$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ": La date de transaction (" . $tx_date . ") n'est pas valide pour le scan de l'utilisateur "
					. $user['id'] . ": " . $user['prenom'] . " " . $user['nom'] . " chez le marchand " . $merchant['name'] . ")";
				}

	//////////////
	// 4.1 If User Never Affiliated to Merchant

				else if ($rowNb == 0)
				{
					$createRelation = "INSERT INTO  marchand_has_mobileuser SET marchand_id='"
					. mysql_real_escape_string($merchant_id)
					. "', mobileuser_id='"
					. mysql_real_escape_string($user_id)
					. "', nb_use='"
					. mysql_real_escape_string("1")
					. "'";
					$resultInsert = mysql_query($createRelation);

					if ($resultInsert == FALSE)
					{
						$status = "warning";
						$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":Erreur lors de l'affiliation au marchand de l'utilisateur " . $user['id'] . ": " . $user['prenom'] . " " . $user['nom'] . " chez le marchand " . $merchant['name'];
					}
					else
					{
						$sqlGetSupa = "SELECT * FROM marchand_has_mobileuser WHERE `mobileuser_id` = '"
							. mysql_real_escape_string($user_id)
							. "' && `marchand_id` = '"
							. mysql_real_escape_string($merchant['supermarchand_id'])
							. "'";
						$resultSupa = mysql_query($sqlGetSupa);
						$rowSupa = mysql_fetch_array($resultSupa);
						$newSupaUse =  intval($rowSupa['nb_use']) + 1 ;
						$updateUse  = "UPDATE marchand_has_mobileuser SET nb_use=$newSupaUse WHERE `mobileuser_id` = '"
							. mysql_real_escape_string($usr_id)
							. "' && `marchand_id` = '"
							. mysql_real_escape_string($merchant['supermarchand_id'])
							. "'";
						$resultUpdate = mysql_query($updateUse);


						if($resultAdd = ajoutPts($merchant, $public_id, $amount))
						{
							$points = json_decode($resultAdd, TRUE);
							$createHisto = "INSERT INTO  authentification SET marchand_id='"
							. mysql_real_escape_string($merchant_id)
							. "', mobileuser_id='"
							. mysql_real_escape_string($user_id)
							. "', authent_date="
							. $tx_date;
							$resultInsert = mysql_query($createHisto);
						}
						else
						{
							$status = "warning";
							$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":Commande de plus de 4000 euros interdite (utilisateur " . $user['id'] . ": " . $user['prenom'] . " " . $user['nom'] . " chez le marchand " . $merchant['name'] . ")";
						}
					}
				}

	//////////////
	// 4.2 If User already Affiliated to Merchant

				else
				{
					//ajout();
					$rowLink = mysql_fetch_array($resultGetCustomer);
					$pastNbUse = intval($rowLink['nb_use']);
					$newNbUse = $pastNbUse + 1;


					///SUPERMARCHAND
					if (intval($merchant['supermarchand_id']) >= 1)
					{
						$sqlGetSupa = "SELECT * FROM marchand_has_mobileuser WHERE `mobileuser_id` = '"
							. mysql_real_escape_string($user_id)
							. "' && `marchand_id` = '"
							. mysql_real_escape_string($merchant['supermarchand_id'])
							. "'";
						$resultSupa = mysql_query($sqlGetSupa);
						$rowSupa = mysql_fetch_array($resultSupa);
						$newSupaUse =  intval($rowSupa['nb_use']) + 1 ;
						$updateUse  = "UPDATE marchand_has_mobileuser SET nb_use=$newSupaUse WHERE `mobileuser_id` = '"
							. mysql_real_escape_string($user_id)
							. "' && `marchand_id` = '"
							. mysql_real_escape_string($merchant['supermarchand_id'])
							. "'";
						$resultUpdate = mysql_query($updateUse);
					}
					$updateUse  = "UPDATE marchand_has_mobileuser SET nb_use=$newNbUse WHERE `mobileuser_id` = '"
						. mysql_real_escape_string($user_id)
						. "' && `marchand_id` = '"
						. mysql_real_escape_string($merchant_id)
						. "'";
					$resultUpdate = mysql_query($updateUse);
					if ($resultUpdate == FALSE){
						$status = "warning";
						$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":Erreur lors de la mise a jour de la base de donnee pour l'utilisateur " . $user['id'] . ": " . $user['prenom'] . " " . $user['nom'] . " chez le marchand " . $merchant['name'];
					}
					else
					{
						if($resultAdd = ajoutPts($merchant, $public_id, $amount))
						{
							$points = json_decode($resultAdd, TRUE);
							$createHisto = "INSERT INTO  authentification SET marchand_id='"
							. mysql_real_escape_string($merchant_id)
							. "', mobileuser_id='"
							. mysql_real_escape_string($user_id)
							. "', authent_date="
							. $tx_date;
							$resultInsert = mysql_query($createHisto);

							if (intval($merchant['supermarchand_id']) >= 1)
							{
								$createHisto = "INSERT INTO authentification SET marchand_id='"
								. mysql_real_escape_string($merchant['supermarchand_id'])
								. "', mobileuser_id='"
								. mysql_real_escape_string($user_id)
								. "', authent_date="
								. $tx_date;
								$resultInsert = mysql_query($createHisto);
							}
						}
						else
						{
							$status = "warning";
							$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":Commande de plus de 1000 euros interdite (" . $user['id'] . ": " . $user['prenom']
							. " " . $user['nom'] . " chez le marchand " . $merchant['name'] . ")";
						}
					}
				}

	//////////////
	// 5. Adding Transaction

				if ($status != "warning")
				{
					$won_pts = '0';
					if (isset($points['transaction']['point']))
						$won_pts = $points['transaction']['point'];

					if ($won_pts > 0)
					{
						$createHisto = "INSERT INTO transaction SET marchand_id='"
								. mysql_real_escape_string($merchant_id)
								. "', mobileuser_id='"
								. mysql_real_escape_string($user_id)
								. "', value='"
								. mysql_real_escape_string($points['transaction']['point'])
								. "', id_loyalty='"
								. mysql_real_escape_string($points['transaction']['publicId'])
								. "', amount='"
								. mysql_real_escape_string($amount)
								. "', transaction_date="
								. $tx_date;
						mysql_query($createHisto);

						$createMessage= "INSERT INTO message SET marchand_id='"
								. mysql_real_escape_string($merchant_id)
								. "', type='"
								. mysql_real_escape_string('recu')
								. "', points='"
								. mysql_real_escape_string($won_pts)
								. "', message='"
								. mysql_real_escape_string("points recus")
								. "', start_date="
								. $tx_date
								. ", is_validated='"
								. mysql_real_escape_string("1")
								. "'";
						mysql_query($createMessage);

						$message_id = mysql_insert_id();
						$linkMsg = "INSERT INTO message_has_mobileuser SET message_id='"
								. mysql_real_escape_string($message_id)
								. "', mobileuser_id='"
								. mysql_real_escape_string($user_id)
								. "', date_creation="
								. $tx_date;
						mysql_query($linkMsg);
					}
				}
			}
		}

		else
		{
			$status = "warning";
			$errorMsg = $errorMsg . "\r\n>Scan #" . $scan_nb . ":";
			if(!isset($merchant['application_id']))$errorMsg = $errorMsg . "Marchand " . $merchant_id . " inexistant";
			else if($merchant['is_active']== 0)$errorMsg = $errorMsg . "Le marchand " . $merchant['name'] . " n'est pas active";
			else if($amount < 0)$errorMsg = $errorMsg . "Le montant saisi pour l'utilisateur " . $user['id'] . ": " . $user['prenom'] . " " . $user['nom']
			. " chez le marchand " . $merchant['name'] . " est invalide (" . $amount . "€)";
			else $errorMsg = $errorMsg . "Erreur inconnue";
		}

	}

	//////////////
	// OUTPUT
	$jsonResult['status'] = $status;
	$jsonResult['message'] = $errorMsg;

	// Envoi d'un mail d'alerte (Ne marche pas pour JNPMC ????!!!??? :
	//if($status == "warning")
	//	mail_youfid("alexandre.crouan@goswiff.com", "to", "subject", "body");

	doLog("Response=" . json_encode(array_map_utf8_encode($jsonResult)));

	doLog("_________________________ \n\n ");
	echo(json_encode(array_map_utf8_encode($jsonResult)));

