<?php
// respond to preflights
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit;
}

require_once 'Logger.class.php';
require_once './Smarty/Smarty.class.php';
require_once '../../lib/db_functions.php';
require_once 'utils.php';

if (!isset($logger)) {
  //$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");
  $logger = new Logger('logs/');
}

function doLog($message) {
  global $logger;
  if (isset($logger)) {
    $logger->log('debug', 'merchantCheckScanUser', "$message\n", Logger::GRAN_MONTH);
  }
}

///////////
//// FUNCTIONS ////
$loginResult = '7e17880d34734a43b83848f76b1452b3';

/**
 * Add points to user for a given merchant to loyalty server.
 * 
 * @param $rowMarchand the merchant row
 * @param $rowUser the user row
 * @param $loginResult the login result
 * @param $jsonArray the input JSON array
 * @return the loyalty response json as string.
 */
function ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray) {
  global $url_loyalty;
  //doLog("Json Array: " . print_r($jsonArray, true));
  $loginResult    = '7e17880d34734a43b83848f76b1452b3';
  $result_accueil = FALSE;
  if (isset($jsonArray->forced_amount)) {
    $accueil_json = '{
                      "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                      "wsAccessToken" : "' . $loginResult . '",
                      "mobileUserPublicId" : "' . $rowUser['public_id'] . '",
                      "applicationPublicId" : "' . $rowMarchand['application_id'] . '",
                      "points" : "' . $jsonArray->forced_amount . '"
                      }';
    // doLog("Loy request: " . $accueil_json);
    $accueil_url    = $url_loyalty . "services/transaction/addpoints";
    $result_accueil = postRequest($accueil_url, $accueil_json);
  } else if ($rowMarchand['is_accueil_client'] == '1') {
    $accueil_json = '{
                      "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                      "wsAccessToken" : "' . $loginResult . '",
                      "mobileUserPublicId" : "' . $rowUser['public_id'] . '",
                      "applicationPublicId" : "' . $rowMarchand['application_id'] . '",
                      "points" : "' . $rowMarchand['points_for_accueil'] . '"
                      }';
    // doLog("Loy request: " . $accueil_json);
    $accueil_url    = $url_loyalty . "services/transaction/addpoints";
    $result_accueil = postRequest($accueil_url, $accueil_json);
  } else if (isset($jsonArray->amount)) {
    if ($jsonArray->amount <= 4000) {
      //doLog("loyalty transaction");
      $transaction_url  = $url_loyalty . "services/transaction";
      $transaction_json = '{
                        "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                        "wsAccessToken" : "' . $loginResult . '",
                        "mobileUserPublicId" : "' . $rowUser['public_id'] . '",
                        "applicationPublicId" : "' . $rowMarchand['application_id'] . '",
                        "amount" : "' . $jsonArray->amount . '"
                    }';
      // doLog("Loy request: " . $transaction_json);
      $result_accueil = postRequest($transaction_url, $transaction_json);
    }
  } else {
    $toadd = $rowMarchand['points_for_accueil'];
    if ($toadd == 0)
      $toadd = 5;
    $accueil_json = '{
                      "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
                      "wsAccessToken" : "' . $loginResult . '",
                      "mobileUserPublicId" : "' . $rowUser['public_id'] . '",
                      "applicationPublicId" : "' . $rowMarchand['application_id'] . '",
                      "points" : ' . $toadd . '
                  }';
    // doLog("Loy request: " . $accueil_json);
    $accueil_url    = $url_loyalty . "services/transaction/addpoints";
    $result_accueil = postRequest($accueil_url, $accueil_json);
  }

  //doLog("Loy response: " . $result_accueil);
  //// UPDATE : LOGIN ////
  return $result_accueil;
}

// GetUserToKnowIfRegister
/**
 * Gets a user's id by its QR code
 * @param $qrcdode the user QR code
 * @return the user id.
 */
function getUserId($qrcode) {
  $sqlGetMerchant = "SELECT id FROM mobileuser WHERE `mail` != '' AND `qr_code` = '" . mysql_real_escape_string($qrcode) . "'";
  $result         = mysql_query($sqlGetMerchant);
  if ($result == FALSE || mysql_num_rows($result) == 0) {
    return ('0');
  }
  $row = mysql_fetch_array($result);
  return ($row['id']);
}

/**
 * Gets a merchant's id by its loyalty application id.
 * @param $appid the loyalty application id.
 * @return the merchant id.
 */
function getMerchantId($appid) {
  $sqlGetCustomer = "SELECT id FROM marchand WHERE `application_id` = '" . mysql_real_escape_string($appid) . "'";
  $result         = mysql_query($sqlGetCustomer);
  if ($result == FALSE) {
    return ('0');
  }
  $row = mysql_fetch_array($result);
  return ($row['id']);
}

/// Verifie si l'email est absent de la db
function checkEmail($mail) {
  return true; // was malfunction and always responding TRUE anyway.
}
//// END FUNCTIONS ////

$youfidDb = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
$youfidDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$json = file_get_contents('php://input');
$headers = array();
if(function_exists('apache_request_headers')) {
  $headers = apache_request_headers();
} else {
  $headers = array(
    'Authorization' => $_SERVER['HTTP_AUTHORIZATION']
  );
}

$authorization = checkRequestAuthorization($youfidDb, $headers, $json);

$jsonArray = json_decode($json);

if(isset($jsonArray->merchant_id)) {
  $query = $youfidDb->prepare("SELECT * FROM marchand WHERE `id` = :id");
  $query->execute(array(
    'id' => $jsonArray->merchant_id
  ));

  $rowMarchand = false;
  if($query->rowCount() === 1) {
    $rowMarchand = $query->fetch(PDO::FETCH_ASSOC);
    // doLog("rowMarchand: " . print_r($rowMarchand, true));
    // doLog("Merchant found " . $rowMarchand['name']);

    if(isSecurityActivated($rowMarchand) && (!$authorization || !$authorization->granted || $rowMarchand['id'] != $authorization->merchantId)) {
      $error = TRUE;
      $errorMsg = 'Not authorized: ' . $authorization->error;

      header("HTTP/1.1 401 Unauthorized");
      die(json_encode(
        array(
          'status' => "error",
          'message' => $errorMsg,
          'code' => 'ERR0401'
        )
      ));
    }
  } else {
    $errorMsg = "Merchant with id " . $jsonArray->merchant_id . " not found";
    
    doLog('ERROR: ' . $errorMsg);
    
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(array(
      'status' => 'error',
      'message' => $errorMsg,
      'code' => 'ERR0400'
    )));
  }
} else {
  header('HTTP/1.1 400 Bad Request');
  die(json_encode(array(
    'status' => 'error',
    'message' => 'Merchant id is missing',
    'code' => 'ERR0400'
  )));
}

$tbl_name = "marchand_has_mobileuser";
require_once('dbLogInfo.php');

////////////////////////////////////////
// Error properties
$error    = false;
$errorMsg = "";
$errorCode = "";
$isReg    = true;

////////////////////////////////////////
// DataBase connection
mysql_connect("$host", "$username", "$password") or die('{"status":"error", "message":"cannot connect to DB"}');
mysql_select_db("$db_name") or die('{"status":"error", "message":"cannot select DB"}');

doLog("Request=" . $json);

// Get app id
//$sqlGetMarchand = "SELECT * FROM marchand WHERE `id` = '" . mysql_real_escape_string($jsonArray->merchant_id) . "'";
//$resultMarchand = mysql_query($sqlGetMarchand);
//$rowMarchand    = mysql_fetch_array($resultMarchand);

//////////////
$JNPMC          = "0";
$ask_phone      = "0";
$upgradeQR      = "0";
$maxChecked     = 0;
$newCard        = 0;

// Si le marchand n'est pas activé
if ($rowMarchand['is_active'] == 0 && $rowMarchand['id'] != 192) {
  $error    = true;
  $errorMsg = "Compte marchand desactive";
  $errorCode = "NO_ACTIVE_MERCHANT";
}

$process = true;
// Process only if not updating phone number
if (isset($jsonArray->qr_code) && isset($jsonArray->phone) && !isset($jsonArray->is_register)) {
  $process = false;
} else if (isset($jsonArray->qr_code) && $rowMarchand['ask_phone'] == 1) {
  // ask_phone
  $sqlGetPhone = "SELECT phone FROM mobileuser WHERE `qr_code` = '" . mysql_real_escape_string($jsonArray->qr_code) . "'";
  $resultPhone = mysql_query($sqlGetPhone);
  $rowPhone    = mysql_fetch_array($resultPhone);
  if (!$rowPhone || $rowPhone['phone'] == "0") {
    $ask_phone = "1";
  }
}

// CAS PARTICULIERS
// MODIF ALEX 03.03.2015
if ($process == false) {
  $status = "ok";
} else if ($jsonArray->is_register == '1' && (!isset($jsonArray->email) || !isset($jsonArray->qr_code))) {
  $error    = true;
  $errorMsg = "Merci de saisir email et qrcode pour le register";
  $errorCode = "NO_MAIL_OR_QRCODE";
} else if (isset($jsonArray->email) && isset($jsonArray->qr_code)) {
  // echo "in cas particulier";
  // REGISTER CARTE PHYSIQUE
  if (isset($jsonArray->is_register) && $jsonArray->is_register == '1') {
    $public_id = gen_uuid();
    
    // echo "<br/>in carte physique";
    /*$mailVerif = verifyEmail($jsonArray->email);
    if (intval($rowMarchand['id']) === 1099 && $mailVerif->status !== 'Ok') {
      $error = true;
      $errorMsg = "L'e-mail que vous avez saisi ne semble pas valide";
      $errorCode = "INVALID_MAIL";
    } else */
    if (checkEmail($jsonArray->email) == TRUE) {
      $sqlEmail    = "SELECT * FROM mobileuser WHERE `mail` = '" . mysql_real_escape_string($jsonArray->email) . "'";
      $resultEmail = mysql_query($sqlEmail);
      
      $query = $youfidDb->prepare("SELECT * FROM mobileuser WHERE `mail` = :mail");
      $query->execute(array(
        'mail' => $jsonArray->email
      ));

      $superMerchant = false;
      if ($query->rowCount() === 1) {
        /*$error    = true;
        $errorMsg = "Erreur : cet e-mail est déjà utilisé. Si vous avez oublié votre mot de passe, merci de vous rendre à cette adresse : http://www.youfid.fr/Account/RecoverPassword";
        $errorCode = "INVALID_MAIL";*/

        $user = fetchOne($query);
        $mercId = $rowMarchand['id'];

        doLog('Found User: ' . print_r($user, true));

        // Check merc link
        $queryCheck = $youfidDb->prepare('SELECT id FROM marchand_has_mobileuser WHERE marchand_id = :merchantId AND mobileuser_id = :userId');
        $queryCheck->execute(array(
          'merchantId' => $rowMarchand['id'],
          'userId' => $user->id
        ));

        doLog('Merchant links #: ' . $queryCheck->rowCount());
        if($queryCheck->rowCount() == 0) {
          // Create link
          $queryLink = $youfidDb->prepare('INSERT INTO marchand_has_mobileuser VALUES (:merchantId, :userId, NULL, 0, NULL, NULL, NOW(), NULL, NULL, 0, NULL, NULL)');
          $linkParams = array(
            'merchantId' => $rowMarchand['id'],
            'userId' => $user->id
          );

          doLog('Merchant link params' . print_r($linkParams, true));

          try {
            $queryLink->execute($linkParams);
            doLog('Merchant link created successfully');
          } catch(PDOException $ex) {
            doLog('Merchant link creation failed' . $ex->getMessage());
          }
        }

        // Update brand card
        $query = $youfidDb->prepare('UPDATE marchand_has_mobileuser SET unvalidated_card = :qrCode WHERE marchand_id = :merchantId AND mobileuser_id = :userId');
        $params = array(
          'qrCode' => $jsonArray->qr_code,
          'merchantId' => $mercId,
          'userId' => $user->id
        );

        // doLog('Params: ' . print_r($params, true));
        if($query->execute($params)) {
          doLog('Unvalidated card update success');
        } else {
          doLog('Unvalidated card update failed');
        }

        $newCard = 1;

        $merchantLanguage = $rowMarchand['lang'];
        // doLog("Merchant: " . print_r($rowMarchand, true));
        //doLog("Merchant lang: " . $merchantLanguage);
        if ($merchantLanguage !== "EN") {
          if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
            $form_text = ' <p style="text-align:center;"><img src="' . $rowMarchand['logo'] . ' " width="300"></p>';
          }

          if (isset($rowMarchand['form_url']) && $rowMarchand['form_url'] != '') {
            $url = $rowMarchand['form_url'] . $jsonArray->qr_code;
          } else {
            //$url = "https://backoffice.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
            $url = "https://api-preprod.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
          }
          
          $url = "<a href='" . $url . "'> Activer mon compte </a>";
          if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
            $form_text .= htmlentities($rowMarchand['form_text'], ENT_QUOTES);
          } else {
            $form_text .= "Nous vous remercions pour votre inscription chez " . $rowMarchand['name'] . ". <br>Pour finaliser la creation de votre compte, veuillez cliquer sur le lien suivant: ";
          }

          $message = $form_text . " " . $url . ".<br/>";
          $message .= "Pensez a télécharger l'application si vous ne l'avez pas encore fait :<br/>" . "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>" . "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";
          // doLog("Mail message: " . $message);
        } else {
          
          if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
            $form_text = ' <p style="text-align:center;"><img src="' . $rowMarchand['logo'] . ' " width="300"></p>';
          }

          if (isset($rowMarchand['form_url']) && $rowMarchand['form_url'] != '') {
            $url = $rowMarchand['form_url'] . $jsonArray->qr_code;
          } else {
            //$url = "https://backoffice.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
            $url = "https://api-preprod.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
          }
          
          $url = "<a href='" . $url . "'> Activate my account</a>";
          if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
            $form_text .= htmlentities($rowMarchand['form_text'], ENT_QUOTES);
          } else {
            $form_text .= "Thank you for registering with " . $rowMarchand['name'] . " . <br>To complete the creation of your account, please click on the following link: ";
          }
          
          $message = $form_text . " " . $url . ".<br/>";
          $message .= "Think of downloading the application if you have not already done so:<br/>" . "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>" . "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";
          // doLog("Merchant lang: " . $merchantLanguage);
        }

        # new queing ...
        $context  = $merchantLanguage === "EN" ? 'preinscription_cartephysique2_en' : 'preinscription_cartephysique2';
        $to_email = $jsonArray->email;
        $to_name  = '';
        $subject  = $merchantLanguage !== "FR" ? 'Activate your account' : 'Activer votre compte';
        $template = 'youfid';
        if ($rowMarchand['supermarchand_id'] > 0) {
          $template = 'supermarchand_' . $rowMarchand['supermarchand_id'];
        }
        
        $from = [ 'email' => 'bienvenue@youfid.fr', 'name' => $rowMarchand['name'] ];
        $to = [ 'email' => $to_email, 'name' => '' ];

        sendgrid_mail($from, $to, $subject, $message);

        /*
        mysql_query("
          INSERT DELAYED INTO `sendmail_queue` (
            `created_at`, `updated_at`,
            `context`, `template`,
            `from_email`, `from_name`,
            `replyto_email`, `replyto_name`,
            `to_email`, `to_name`,
            `subject`,
            `message`,
            `body_txt`,
            `body_html`
          ) VALUES (
            NOW(), NOW(),
            '" . $context . "', '" . $template . "',
            '" . mysql_real_escape_string('bienvenue@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
            '" . mysql_real_escape_string('contact@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
            '" . mysql_real_escape_string($to_email) . "', '" . mysql_real_escape_string($to_name) . "',
            '" . mysql_real_escape_string($subject) . "',
            '" . mysql_real_escape_string($message) . "',
            '',
            ''
          );
        ");
        */
        // Otherwise create it
      } else {
        $sqlQR    = "SELECT * FROM mobileuser WHERE `qr_code` = '" . mysql_real_escape_string($jsonArray->qr_code) . "'";
        $resultQR = mysql_query($sqlQR);
        $userQR   = mysql_fetch_array($resultQR);
        
        if ($userQR['qr_code'] > 0) {
          $upgradeQR = "1";
        }

        if ($upgradeQR == '1') {
          // $sqlCarte = "UPDATE mobileuser SET `mail`='" . mysql_real_escape_string($jsonArray->email) . "', `first_merchant`='" . mysql_real_escape_string($rowMarchand['name']) . "', `status`='" . mysql_real_escape_string(2) . "', `date_inscription`=NOW()" . ", `validation` = MD5(CONCAT(2, '" . mysql_real_escape_string($jsonArray->email) . "'))";
          $sqlCarte = "UPDATE mobileuser SET `first_merchant`='" . mysql_real_escape_string($rowMarchand['name']) . "', `status`='" . mysql_real_escape_string(2) . "', `date_inscription`=NOW()" . ", `validation` = MD5(CONCAT(2, '" . mysql_real_escape_string($jsonArray->email) . "'))";
        } else {
          $sqlCarte = "INSERT INTO mobileuser SET `public_id`='" . mysql_real_escape_string($public_id)
                    . "', `mail`='" . mysql_real_escape_string($jsonArray->email)
                    . "', `first_merchant`='" . mysql_real_escape_string($rowMarchand['name'])
                    . "', `status`='" . mysql_real_escape_string(2)
                    . "', `qr_code`='" . mysql_real_escape_string($jsonArray->qr_code)
                    . "', `date_inscription`=NOW()"
                    . ", `validation` = MD5(CONCAT(2, '" . mysql_real_escape_string($jsonArray->email). "'))"
                    . ", `unsubscribe` = " . (isset($jsonArray->optout) && $jsonArray->optout == '1' ? 1 : 0);
        }

        // Update mobileuser
        if ($upgradeQR == '1') {
          $sqlCarte = $sqlCarte . " WHERE `qr_code` = '" . $userQR['qr_code'] . "'";
        }

        $resultPhys = mysql_query($sqlCarte);
        //$logger->log('debug', 'registerCustomer', "physique query = " . $sqlCarte, Logger::GRAN_MONTH);

        if ($resultPhys == FALSE) {
          $error    = true;
          $errorMsg = "Erreur dans le processus de creation du nouveau user";
          $errorCode = "UNKNOW_ERROR";
        } else {
          if ($upgradeQR != '1') {
            $mobileUserId = mysql_insert_id();
          }

          $merchantLanguage = $rowMarchand['lang'];
          // doLog("Merchant: " . print_r($rowMarchand, true));
          //doLog("Merchant lang: " . $merchantLanguage);
          if ($merchantLanguage !== "EN") {
            if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
              $form_text = ' <p style="text-align:center;"><img src="' . $rowMarchand['logo'] . ' " width="300"></p>';
            }

            if (isset($rowMarchand['form_url']) && $rowMarchand['form_url'] != '') {
              $url = $rowMarchand['form_url'] . $jsonArray->qr_code;
            } else {
              $url = "https://backoffice.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
            }
            
            $url = "<a href='" . $url . "'> Activer mon compte </a>";
            if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
              $form_text .= htmlentities($rowMarchand['form_text'], ENT_QUOTES);
            } else {
              $form_text .= "Nous vous remercions pour votre inscription a YouFID. <br>Pour finaliser la creation de votre compte, veuillez cliquer sur le lien suivant: ";
            }

            $message = $form_text . " " . $url . ".<br/>";
            $message .= "Pensez a télécharger l'application si vous ne l'avez pas encore fait :<br/>" . "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>" . "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";
            // doLog("Mail message: " . $message);
          } else {
            
            if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
              $form_text = ' <p style="text-align:center;"><img src="' . $rowMarchand['logo'] . ' " width="300"></p>';
            }

            if (isset($rowMarchand['form_url']) && $rowMarchand['form_url'] != '') {
              $url = $rowMarchand['form_url'] . $jsonArray->qr_code;
            } else {
              $url = "http://backoffice.youfid.fr/activateAccount.php?qr_code=" . $jsonArray->qr_code;
            }
            
            $url = "<a href='" . $url . "'> Activate my account</a>";
            if (isset($rowMarchand['form_text']) && $rowMarchand['form_text'] != '') {
              $form_text .= htmlentities($rowMarchand['form_text'], ENT_QUOTES);
            } else {
              $form_text .= "Thank you for registering with YouFID. <br>To complete the creation of your account, please click on the following link: ";
            }
            
            $message = $form_text . " " . $url . ".<br/>";
            $message .= "Think of downloading the application if you have not already done so:<br/>" . "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>" . "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";
            // doLog("Merchant lang: " . $merchantLanguage);
          }

          # new queing ...
          $context  = $merchantLanguage === "EN" ? 'preinscription_cartephysique2_en' : 'preinscription_cartephysique2';
          $to_email = $jsonArray->email;
          $to_name  = '';
          $subject  = $merchantLanguage !== "FR" ? 'Activate your account' : 'Activer votre compte';
          $template = 'youfid';
          if ($rowMarchand['supermarchand_id'] > 0) {
            $template = 'supermarchand_' . $rowMarchand['supermarchand_id'];
          }
          
          mysql_query("
            INSERT DELAYED INTO `sendmail_queue` (
              `created_at`, `updated_at`,
              `context`, `template`,
              `from_email`, `from_name`,
              `replyto_email`, `replyto_name`,
              `to_email`, `to_name`,
              `subject`,
              `message`,
              `body_txt`,
              `body_html`
            ) VALUES (
              NOW(), NOW(),
              '" . $context . "', '" . $template . "',
              '" . mysql_real_escape_string('bienvenue@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
              '" . mysql_real_escape_string('contact@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
              '" . mysql_real_escape_string($to_email) . "', '" . mysql_real_escape_string($to_name) . "',
              '" . mysql_real_escape_string($subject) . "',
              '" . mysql_real_escape_string($message) . "',
              '',
              ''
            );
          ");
          
          /*
           * Check if UBERISATION exists
           */
          //$rowMarchand if($upgradeQR != '1') {
          if (!empty($jsonArray->email) && $upgradeQR != '1' && isset($rowMarchand) && $rowMarchand['survey_desk'] == '1') {
            $sqlGetQuestions = "SELECT sq.id, sq.question, sr.result
                FROM survey_questions sq
                LEFT JOIN survey_results sr ON sr.question_id = sq.id AND sr.user_id = '" . $mobileUserId . "'
                WHERE sq.marchand_id = '" . mysql_real_escape_string($rowMarchand['id']) . "'";
            $resultQuestion  = mysql_query($sqlGetQuestions);
            $question        = null;
            while ($rowQuestion = mysql_fetch_array($resultQuestion)) {
              if ($rowQuestion['result'] == null) {
                $question = $rowQuestion;
                break;
              }
            }

            if(!empty($question) && !empty($question['id'])) {    
              // Insert survey question
              mysql_query("INSERT INTO survey_results VALUES(NULL, " . $question['id'] . ", " . $rowMarchand['id'] . ", " . $mobileUserId . ", NULL, NOW(), " . (empty($rowMarchand['supermarchand_id']) ? 'NULL' : $rowMarchand['supermarchand_id'] ) . ")");
              $surveyId  = mysql_insert_id();
          
              // Send survey mail if user has mail defined
              $fullName  = "";
              $now       = new DateTime('NOW');
              $now       = $now->add(new DateInterval('PT30M'));
              $delayDate = $now->format('Y-m-d H:i:s');
              $smarty    = new Smarty();
              $smarty->assign('surveyLink', 'http://www.youfid.fr/membres/surveys/' . $rowMarchand['id'] . '/' . $mobileUserId . '/' . $surveyId . "?single=true&ts=" . time());
              $smarty->assign('email', $jsonArray->email);
              $smarty->assign('merchantName', $rowMarchand['name']);
              $smarty->assign('merchantLogo', $rowMarchand['logo']);
              $message       = $smarty->fetch('./mailerdaemon2/survey.tpl');
              $surveyMailQry = "INSERT DELAYED INTO sendmail_queue (created_at, context, status, template, from_email, from_name,
                replyto_email,replyto_name, to_email, to_name, subject, message, delay_until)
                VALUES (NOW(), 'survey', 'created', 'survey', 'bienvenue@youfid.fr', 'YouFID Team',
                'contact@youfid.fr', 'YouFID Team', '" . mysql_real_escape_string($jsonArray->email) . "', '" . mysql_real_escape_string($fullName) . "',
                'Enquete de satisfaction', '" . mysql_real_escape_string($message) . "', '" . $delayDate . "')";
              doLog("\nSurvey mail qry: \n\n" . $surveyMailQry . "\n\n");
              mysql_query($surveyMailQry);
            }
          }
        }
      }
    } else {
      $error    = true;
      $errorMsg = "Error: There already is an user with the email::" . $jsonArray->email;
      $errorCode = "MAIL_IN_USE";
    }
  } else {
    // JE N'AI PAS MA CARTE
    $sqlEmail   = "SELECT * FROM mobileuser WHERE `mail` = '" . mysql_real_escape_string($jsonArray->email) . "'";
    $result     = mysql_query($sqlEmail);
    $sqlVerifQR = "SELECT * FROM mobileuser WHERE `qr_code` = '" . mysql_real_escape_string($jsonArray->qr_code) . "'";
    $result2    = mysql_query($sqlVerifQR);
    
    if ($result == FALSE || $result2 == FALSE) {
      $error    = TRUE;
      $errorMsg = "Erreur de database.";
      $errorCode = "DB_ERROR";
    } else {
      if (mysql_num_rows($result2)) {
        $error    = TRUE;
        $errorMsg = "Ce QR code est déjà utilisé.";
        $errorCode = "QRCODE_IN_USE";
      } else if (mysql_num_rows($result)) {
        // MODIF ALEX 03.03.2015
        // Update Phone Number if provided
        //          if(isset($jsonArray->email) && isset($jsonArray->phone)){
        //            $ins = 'UPDATE mobileuser SET phone="' . $jsonArray->phone .'" WHERE mail = ' . $jsonArray->email;
        //            mysql_query($ins);
        //            $ask_phone = "0";
        //          }
        $customerRow = mysql_fetch_array($result);
        // On assigne l'id au user
        $JNPMC       = $customerRow['id'];
        // On indique à la tablete que le téléphone à déjà été fourni
        //          if($customerRow['phone'] != '-1' && $customerRow['phone'] != '0') $ask_phone = "0";
        if ($customerRow['phone'] != '0') {
          $ask_phone = "0";
        }

        $_lien_href       = "http://www.youfid.fr/membres/carte/" . $customerRow['validation'] . "/" . $jsonArray->qr_code . "/";
        $merchantLanguage = $rowMarchand['lang'];
        if ($merchantLanguage !== "EN") {
          // On envoie un mail
          //mail_youfid($jsonArray->email/*, $customerRow['prenom'] . " " . $customerRow['nom']*/, "Oubli de carte YouFid", "Vous avez récemment oublié votre carte YouFid et avez utilisé une carte de substitution. Si vous souhaitez désormais utiliser cette dernière en lieu et place de votre ancienne carte, merci de cliquer sur le lien suivant : http://www.youfid.fr/membres/carte/".$customerRow['validation']."/".$jsonArray->qr_code."/", $customerRow['prenom'] . " " . $customerRow['nom']);
          $message = "Vous avez recemment oublie votre carte YouFid et avez utilise une carte de substitution. Si vous souhaitez desormais utiliser cette derniere en lieu et place de votre ancienne carte, merci de cliquer sur le lien suivant : <a href=\"" . $_lien_href . "\">" . $_lien_href . "</a>";
          $subject = 'Oubli de carte YouFid';
          $context = 'oublidecarte';
        } else {
          $message = '<p style="font-weight: bold">Lost Card</p><br /><br />';
          $message .= "You have recently forgotten your YouFID card and used a substitute card. If you want to use the latter instead of your old card, please click on the following link : <a href=\"" . $_lien_href . "\">" . $_lien_href . "</a>";
          $subject = 'Lost Card';
          $context = 'lostcard';
        }

        # new queing ...
        $to_email         = $jsonArray->email;
        $to_name          = $customerRow['prenom'] . " " . $customerRow['nom'];
        $lostCardtemplate = "lostcard_" . $rowMarchand['supermarchand_id'];
        mysql_query("
          INSERT DELAYED INTO `sendmail_queue` (
            `created_at`, `updated_at`,
            `context`, `template`,
            `from_email`, `from_name`,
            `replyto_email`, `replyto_name`,
            `to_email`, `to_name`,
            `subject`,
            `message`,
            `body_txt`,
            `body_html`
          ) VALUES (
            NOW(), NOW(),
            '" . $context . "', '" . $lostCardtemplate . "',
            '" . mysql_real_escape_string('bienvenue@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
            '" . mysql_real_escape_string('contact@youfid.fr') . "', '" . mysql_real_escape_string('YouFID Team') . "',
            '" . mysql_real_escape_string($to_email) . "', '" . mysql_real_escape_string($to_name) . "',
            '" . mysql_real_escape_string($subject) . "',
            '" . mysql_real_escape_string($message) . "',
            '',
            ''
          )");
      } else {
        $error    = TRUE;
        $errorMsg = "E-mail introuvable";
        $errorCode = "MAIL_UNKNOWN";
      }
    }
  }
}

// MODIF ALEX 03.03.2015 : add "if $process = true"
if ($error != TRUE && $process == true && ((isset($jsonArray->qr_code) || isset($jsonArray->usr_id)) && isset($jsonArray->merchant_id) && $maxChecked == 0)) {
  // doLog("Processing code: " . $jsonArray->qr_code);
  if (isset($jsonArray->qr_code) && $JNPMC == '0') {
    // $usr_id = getUserId($jsonArray->qr_code);
    doLog('json array => ' . print_r($jsonArray, true));
    doLog('merchant => ' . print_r($rowMarchand, true));

    $mbu = get_mobile_user_by_qrcode($youfidDb, $jsonArray->qr_code, $rowMarchand, doLog);
    if(!$mbu && $newCard) {
      $query = $youfidDb->prepare('SELECT m.* FROM marchand_has_mobileuser mm INNER JOIN mobileuser m ON m.id = mm.mobileuser_id WHERE unvalidated_card = :qrCode');
      $query->execute(array(
        'qrCode' => $jsonArray->qr_code
      ));

      if($query->rowCount() > 0) {
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $mbu = $query->fetch();
      }
    }

    doLog('getUserId => ' . print_r($mbu, true));
    $usr_id = $mbu['id'];
    doLog("Found user id: " . $usr_id);
  } else if ($JNPMC != '0') { // JNPMC
    $usr_id = $JNPMC;
  } else {
    $usr_id = $jsonArray->usr_id;
  }

  if ($usr_id == '0') {
    //doLog("No valid user");
    $isReg = false;
    $error = true;
  } else {
    /// NEW
    $capping = intval($rowMarchand['capping']);
    $nbScan  = "SELECT `transaction_date` FROM `transaction` WHERE transaction_date BETWEEN DATE_SUB(NOW(), INTERVAL " . $capping . " SECOND) AND NOW()" . " AND `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' AND `marchand_id` = '" . mysql_real_escape_string($jsonArray->merchant_id) . "' AND `value` > 0";
    
    //doLog("Query::" . $nbScan);
    $maxChecked = 0;
    $resultScan = mysql_query($nbScan);
    $totalScan  = mysql_num_rows($resultScan);
    
    //doLog("TotalScan::" . $totalScan . " maxscan::" . $rowMarchand['max_scan']);
    $maxScan = intval($rowMarchand['max_scan']);
  
    if ($maxScan == 0) {
      $maxScan = 1;
    }

    if ($totalScan >= $maxScan) {
      //doLog("Max scans reached");
      $error      = true;
      $maxChecked = 1;
    } else {
      //doLog("Max scans NOT reached");
      $merchant_id = $jsonArray->merchant_id;
      subscribe_user_to_sm($merchant_id, $usr_id);
      
      // VERIF SI LIEN DEJA EFFECTUE
      $sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' AND `marchand_id` = '" . mysql_real_escape_string($merchant_id) . "'";
      $result         = mysql_query($sqlGetCustomer);
      
      if(!$result) {
        doLog("No link between user $usr_id and merchant $merchant_id found in database");
      }

      /// Get public user ID
      $sqlGetUser     = "SELECT * FROM mobileuser WHERE `id` = '" . mysql_real_escape_string($usr_id) . "'";
      $result2        = mysql_query($sqlGetUser);
      $rowUser        = mysql_fetch_array($result2);
      
      // TEST IF USER ON FID SERV
      //$test_url = $url_loyalty . 'services/user/login';
      $test_url       = $url_loyalty . 'services/mobileuser/mobiuserapp';
      $json_test      = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "' . '7e17880d34734a43b83848f76b1452b3' . '", "mobileUserPublicId":"' . $rowUser['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
      
      //// END UPDATE : LOGIN ////
      //doLog("===> Loyalty login ");
      $resultPts      = postRequest($test_url, $json_test);
      $testResult     = json_decode($resultPts, true);
          
      //doLog("Test results: " . ($testResult['mobileUserApplication'] == NULL && $upgradeQR == '0' ? 'true' : 'false'));
      if ($testResult['mobileUserApplication'] == NULL && $upgradeQR == '0') {
        //inscription
        $json_insri    = '{
          "wsAccessPublicKey":"8293582c-1e0c-40ff-9d59-10cb18834855",
          "wsAccessToken" : "' . '7e17880d34734a43b83848f76b1452b3' . '",
          "mobileUserPublicId" : "' . $rowUser['public_id'] . '",
          "applicationPublicId" : "' . $rowMarchand['application_id'] . '",
          "points" : 0
        }';

        //// END UPDATE : LOGIN ////
        $inscri_url    = $url_loyalty . "services/mobileuser";
        $result_inscri = postRequest($inscri_url, $json_insri);
        $inscriResult  = json_decode($result_inscri, true);
      }

      if ($result == false) {
        $error    = true;
        $errorMsg = "ERR1: Erreur avec la base de données";
        $errorCode = "DB_ERROR";
        //doLog("Error: " . $errorMsg);
      } else if(isMobileUserBolcked($rowUser["id"], $rowMarchand["supermarchand_id"]) || 
        ($rowMarchand["supermarchand_id"] == 699 && !fitOtacosSecurityRules(intval($rowMarchand["id"]), 699, $rowUser["id"]))) {
        $error    = true;
        $errorMsg = "Votre compte est bloqué temporairement. Merci de contacter le service client";
        $errorCode = "USER_BLOCKED";
      } else {
        // Check max points
        $maxPoints = 0; // No limit
        if(!empty($rowMarchand["max_points"]) && intval($rowMarchand["max_points"]) > 0) {
          $maxPoints = intval($rowMarchand["max_points"]);
        } else if(!empty($rowMarchand["supermarchand_id"]) && intval($rowMarchand["supermarchand_id"]) > 0) {
          $superMerchant = get_merchant_2($rowMarchand["supermarchand_id"]);
          if($superMerchant && !empty($superMerchant["max_points"]) && intval($superMerchant["max_points"]) > 0) {
            $maxPoints = intval($superMerchant["max_points"]);
          }
        }
        
        $userPoints = intval($testResult["mobileUserApplication"]["totalPoints"]);
        if($maxPoints && $userPoints >= $maxPoints) {
          // Check max points.
          // Pacth for OTacos: Otacos points limited to 250 by default.
          $maxPointsReached = true;
        } else {
          ////// Add ALEX MAJ mobileuser->nb_use //////
          //doLog("Not error");
          $updateNbUse       = "UPDATE mobileuser SET nb_use= nb_use + 1 WHERE `id` = '" . mysql_real_escape_string($usr_id) . "'";
          
          //doLog("Update nuUse qr: " . $updateNbUse);
          $resultUpdateNbUse = mysql_query($updateNbUse);
          
          /////////////////////////////////////////////
          $rowNb             = mysql_num_rows($result);
          if ($rowNb == 0) {
            // Relation jamais créée, donc création.
            $createRelation = "INSERT INTO  $tbl_name SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', nb_use='" . mysql_real_escape_string("1") . "'";
            $resultInsert   = mysql_query($createRelation);
            if ($resultInsert == FALSE) {
              $error    = true;
              $errorMsg = "ERR2: Erreur avec la base de données: " . print_r(mysql_error(), true);
              $errorCode = "DB_ERROR";
            } else {
              $sqlGetSupa   = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' && `marchand_id` = '" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "'";
              $resultSupa   = mysql_query($sqlGetSupa);
              $rowSupa      = mysql_fetch_array($resultSupa);
              $newSupaUse   = intval($rowSupa['nb_use']) + 1;
              $updateUse    = "UPDATE $tbl_name SET nb_use=$newSupaUse WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' && `marchand_id` = '" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "'";
              $resultUpdate = mysql_query($updateUse);

              // Add points to user
              if ($resultAdd = ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray)) {
                $points       = json_decode($resultAdd, TRUE);
                $createHisto  = "INSERT DELAYED INTO  authentification SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', authent_date=NOW()";
                $resultInsert = mysql_query($createHisto);
              } else {
                $error    = true;
                $errorMsg = "Merci de ne pas saisir de commande de plus de 4000 euros";
                $errorCode = "AMOUNT_TOO_HIGH";
              }
            }
          } else {
            // Relation existante, ajout des points
            //doLog("Relation existante");
            //doLog("merchant: " . print_r($rowMarchand, true));
            //ajout();
            $rowLink   = mysql_fetch_array($result);
            $pastNbUse = intval($rowLink['nb_use']);
            $newNbUse  = $pastNbUse + 1;
            
            ///SUPERMARHCND
            if (intval($rowMarchand['supermarchand_id']) >= 1) {
              $sqlGetSupa   = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' && `marchand_id` = '" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "'";
              $resultSupa   = mysql_query($sqlGetSupa);
              $rowSupa      = mysql_fetch_array($resultSupa);
              $newSupaUse   = intval($rowSupa['nb_use']) + 1;
              $updateUse    = "UPDATE $tbl_name SET nb_use=$newSupaUse WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' && `marchand_id` = '" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "'";
              $resultUpdate = mysql_query($updateUse);
            }
            
            $updateUse    = "UPDATE $tbl_name SET nb_use=$newNbUse WHERE `mobileuser_id` = '" . mysql_real_escape_string($usr_id) . "' && `marchand_id` = '" . mysql_real_escape_string($merchant_id) . "'";
            $resultUpdate = mysql_query($updateUse);
            if ($resultUpdate == FALSE) {
              $error    = true;
              $errorMsg = "ERR3: Erreur avec la base de données";
              $errorCode = "DB_ERROR";
            } else {
              // Add points to user.
              //doLog("Ajouts point");
              if ($resultAdd = ajoutPts($rowMarchand, $rowUser, $loginResult, $jsonArray)) {
                $points       = json_decode($resultAdd, TRUE);
                $createHisto  = "INSERT DELAYED INTO  authentification SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', authent_date=NOW()";
                $resultInsert = mysql_query($createHisto);
                if (intval($rowMarchand['supermarchand_id']) >= 1) {
                  $createHisto  = "INSERT DELAYED INTO authentification SET marchand_id='" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', authent_date=NOW()";
                  $resultInsert = mysql_query($createHisto);
                }
              } else {
                $error    = true;
                $errorMsg = "Merci de ne pas saisir de commande de plus de 1000 euros";
                $errorCode = "AMOUNT_TOO_HIGH";
              }
            }
          }
        }
      }
    }
  }
} else if ($process == true && $error != TRUE) {
  // doLog("Break 1");
  $error    = true;
  $errorMsg = "Paramètres incorrects";
  $errorCode = "BAD_PARAMS";
}

if ($error == true) {
  // doLog("Break 2");
  if ($isReg == true) {
    $status = "error";
  } else {
    $status = "register";
  }

  if ($maxChecked == 1 || (isset($maxPointsReached) && $maxPointsReached)) {
    $merchant_id                         = $jsonArray->merchant_id;
    $max                                 = '1';
    $errorMsg                            = isset($maxPointsReached) && $maxPointsReached ? "Max points reached" : "";
    $errorCode                           = isset($maxPointsReached) && $maxPointsReached ? "MAX_POINTS_REACHED" : "MAX_SCAN_REACHED";
    $status                              = "ok";
    $jsonResult['usr_id']                = $usr_id;
    
    /// Get public user ID
    $sqlGetUser                          = "SELECT * FROM mobileuser WHERE `id` = '" . mysql_real_escape_string($usr_id) . "'";
    $result2                             = mysql_query($sqlGetUser);
    $rowUser                             = mysql_fetch_array($result2);
    
    // Get app id
    $sqlGetMarchand                      = "SELECT * FROM marchand WHERE `id` = '" . mysql_real_escape_string($jsonArray->merchant_id) . "'";
    $resultMarchand                      = mysql_query($sqlGetMarchand);
    $rowMarchand                         = mysql_fetch_array($resultMarchand);
    
    //////////////
    $jsonResult['first_name']            = $rowUser['prenom'];
    $jsonResult['last_name']             = $rowUser['nom'];
    $jsonResult['fid_status']            = $rowUser['fid_status'];
    $jsonResult['won_pts']               = '0';
    $jsonResult['maximum_scans_reached'] = $max;
    $pts_url                             = $url_loyalty . 'services/mobileuser/mobiuserapp';
    
    //// UPDATE LOGIN ////
    $json_pts                            = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "' . '7e17880d34734a43b83848f76b1452b3' . '", "mobileUserPublicId":"' . $rowUser['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
    
    //// END UPDATE LOGIN ////
    $resultPts                           = postRequest($pts_url, $json_pts);
    $ptsResult                           = json_decode($resultPts, true);
    $jsonResult['total_pts']             = $ptsResult['mobileUserApplication']['totalPoints'];
    
    //$jsonResult['pin_user_is_active'] = $rowUser['is_pin_active'];
    if ($rowUser['is_pin_active'] == '1') {
      $jsonResult['pin_user'] = $rowUser['pin_code'];
    }

    //$jsonResult['pin_merchant_is_active'] = $rowMarchand['is_pin_marchand'];
    //$jsonResult['pin_merchant'] = $rowMarchand['pin_code'];
    // ????? //
    $createHisto2 = "INSERT DELAYED INTO  authentification SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', authent_date=NOW()";
    $resultInsert = mysql_query($createHisto2);
    if (intval($rowMarchand['supermarchand_id']) >= 1) {
      $createHisto  = "INSERT DELAYED INTO  authentification SET marchand_id='" . mysql_real_escape_string($rowMarchand['supermarchand_id']) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', authent_date=NOW()";
      $resultInsert = mysql_query($createHisto);
    }
  }
} else if ($process == true) {
  $status                   = "ok";
  $errorMsg                 = isset($maxPointsReached) && $maxPointsReached ? "Max points reached" : $errorMsg;
  $errorCode                = isset($maxPointsReached) && $maxPointsReached ? "MAX_POINTS_REACHED" : "";
  $jsonResult['usr_id']     = $usr_id;
  $jsonResult['first_name'] = $rowUser['prenom'];
  $jsonResult['last_name']  = $rowUser['nom'];
  $jsonResult['fid_status'] = $rowUser['fid_status'];

  if (isset($points['transaction']['point'])) {
    $jsonResult['won_pts'] = $points['transaction']['point'];
  } else {
    $jsonResult['won_pts'] = '0';
  }
  
  if (isset($points['mobileUserApplication']['totalPoints'])) {
    $jsonResult['total_pts'] = $points['mobileUserApplication']['totalPoints'];
  } else {
    $pts_url                 = $url_loyalty . 'services/mobileuser/mobiuserapp';
    $json_pts                = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "' . '7e17880d34734a43b83848f76b1452b3' . '", "mobileUserPublicId":"' . $rowUser['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
    
    //// END UPDATE LOGIN ////
    $resultPts               = postRequest($pts_url, $json_pts);
    $ptsResult               = json_decode($resultPts, true);
    $jsonResult['total_pts'] = $ptsResult['mobileUserApplication']['totalPoints'];
  }
  
  // JULIEN PIN
  //    $jsonResult['pin_user_is_active'] = $rowUser['is_pin_active'];
  //    $jsonResult['pin_user'] = $rowUser['pin_code'];
  //    $jsonResult['pin_merchant_is_active'] = $rowMarchand['is_pin_marchand'];
  //    $jsonResult['pin_merchant'] = $rowMarchand['pin_code'];
  if ($rowUser['is_pin_active'] == '1') {
    $jsonResult['pin_user'] = $rowUser['pin_code'];
  }
  
  if ($jsonResult['won_pts'] > 0) {
    $createHisto2 = "INSERT DELAYED INTO  transaction SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', value='" . mysql_real_escape_string($points['transaction']['point']) . "', id_loyalty='" . mysql_real_escape_string($points['transaction']['publicId']) . "', amount='" . mysql_real_escape_string($jsonArray->amount) . "', transaction_date=NOW()";
    $resultInsert = mysql_query($createHisto2);
    $createHisto3 = "INSERT DELAYED INTO  message SET marchand_id='" . mysql_real_escape_string($merchant_id) . "', type='" . mysql_real_escape_string('recu') . "', points='" . mysql_real_escape_string($jsonResult['won_pts']) . "', message='" . mysql_real_escape_string("points recus") . "', start_date=NOW()" . ", is_validated='" . mysql_real_escape_string("1") . "'";
    $resultInsert = mysql_query($createHisto3);
    $message_id   = mysql_insert_id();
    $linkMsg      = "INSERT DELAYED INTO  message_has_mobileuser SET message_id='" . mysql_real_escape_string($message_id) . "', mobileuser_id='" . mysql_real_escape_string($usr_id) . "', date_creation=NOW()";
    $resultInsert = mysql_query($linkMsg);
  }
} else {
  $errorMsg = "Votre numero de telephone a ete pris en compte";
}

// Update Phone Number if provided
if (isset($jsonArray->qr_code) && isset($jsonArray->phone)) {
  if (isset($jsonArray->email)) {
    $ins = 'UPDATE mobileuser SET phone="' . $jsonArray->phone . '" WHERE mail = "' . $jsonArray->email . '"';
  } else {
    $ins = 'UPDATE mobileuser SET phone="' . $jsonArray->phone . '" WHERE qr_code = ' . $jsonArray->qr_code;
  }
  
  mysql_query($ins);
  $ask_phone = "0";
}

$jsonResult['status']    = $status;
$jsonResult['ask_phone'] = $ask_phone;
$jsonResult['message']   = $errorMsg;
$jsonResult['code'] = $errorCode;
$jsonResult['newCard'] = $newCard;

// $logger->log('debug', 'checkScanUser', "Response=" . json_encode(array_map_utf8_encode($jsonResult)), Logger::GRAN_MONTH);
doLog("Response=" . json_encode(array_map_utf8_encode($jsonResult)));
$jsonResult = array_map_utf8_encode($jsonResult);
echo (json_encode(array_map_utf8_encode($jsonResult)));

/**
 * ============================================================
 * ================= Otacos security functions ================
 * ============================================================
 */

 /**
  * Check if dates are consecutives
  */
function checkCountinousDates(/*.array.*/ $datesArray) {
  if(count($datesArray)) {
    $previous = $datesArray[0];
    unset($datesArray[0]);

    foreach($datesArray as $date) {
      // echo "date: " . $date->format('Y-m-d H:i:s') . "\n";
      $diff = $date->diff($previous);
      if($diff->days === 1) {
        $previous = $date;
      } else {
        return false;
      }
    }
  } else {
    return false;
  }

  return true;
}

/**
 * Block a mobile user
 */
function blockMobileUser(/*.int.*/ $mobileUserId, /*.int.*/ $merchandId) {
  $blockCount = 0;

  // Get block count
  $mercMobileUser = getMarchandMobileUser($mobileUserId, $merchandId);
  if($mercMobileUser) {
    $blockCount = intval($mercMobileUser["block_count"]);
  }

  $blockDays = 2;
  if($blockCount === 1) {
    $blockDays = 5;
  } else if($blockCount > 1) {
    $blockDays = 30;
  }

  $blockEnd = new DateTime();
  $blockEnd->add(new DateInterval("P" . $blockDays . "D"));
  
  $blockCount = $blockCount + 1;
  $blocQuery = "UPDATE marchand_has_mobileuser
    SET block_date = NOW(), block_end = '" . $blockEnd->format("Y-m-d H:i:s") . "', block_count = " . $blockCount . "
    WHERE mobileuser_id = " . intval($mobileUserId) . "
    AND marchand_id = " . intval($merchandId);

  mysql_query($blocQuery);
}

/**
 * Checks if user fit Otacos security rules
 */
function fitOtacosSecurityRules(/*.int.*/ $merchandId, /*.int.*/ $superMerchantId, /*.int.*/ $mobileUserId) {
  // Merchants whitelist
  $whitelist = array(
    890389 // Otacos les Ulys
  );
  
  if(in_array($merchandId, $whitelist)) {
    return true;
  }
  
  if(intval(date('H')) > 4 && intval(date('H')) < 10) {
    blockMobileUser($mobileUserId, $superMerchantId);
    return false;
  }

  $startDate = new DateTime("2017-12-19 00:00:00");
  $mercMobileUser = getMarchandMobileUser($mobileUserId, $superMerchantId);
  if($mercMobileUser && !empty($mercMobileUser["block_end"])) {
    $startDate = new DateTime($mercMobileUser["block_end"]);
  }

  // Check if has 4 consecutive days of scan
  $now = new DateTime();
  doLog("Start date: " . $startDate->format("Y-m-d H:i:s"));
  doLog("Now: " . $now->format("Y-m-d H:i:s"));
  $diff = $now->diff($startDate);
  
  doLog("Now - Start: " . $diff->format('%R%a'));
  if(intval($diff->format('%R%a')) >= 4) {
    doLog("Check 4 consecutives..");
    $moreThan4ConsecutiveDays = false;
    $query = "SELECT DATE_FORMAT(t.`transaction_date`, '%Y-%m-%d') day, COUNT(t.id) nbTrans
              FROM `transaction` t
              INNER JOIN marchand m ON m.id = t.marchand_id
              WHERE m.supermarchand_id = " . intval($superMerchantId) . "
              AND t.mobileuser_id = " . intval($mobileUserId) . "
              AND t.`transaction_date` > DATE_SUB(NOW(), INTERVAL 4 DAY)
              AND t.`value` > 0
              GROUP BY day
              ORDER BY day DESC";

    $queryRes = mysql_query($query);
    if(mysql_num_rows($queryRes) >= 4) {
      $transactionHistory = array();
      while($resRow = mysql_fetch_array($queryRes)) {
        array_push($transactionHistory, DateTime::createFromFormat('Y-m-d', $resRow[0]));
      }

      $moreThan4ConsecutiveDays = checkCountinousDates($transactionHistory);
    }

    if($moreThan4ConsecutiveDays) {
      blockMobileUser($mobileUserId, $superMerchantId);
      return false;
    }
  }

  $nbDays = 30;
  $nbDays = min(abs(intval($diff->format('%R%a'))), 30);

  doLog("Nb days: $nbDays");

  // Check if has 15 scans in a month
  $query = "SELECT COUNT(t.id) nbTrans
            FROM `transaction` t
            INNER JOIN marchand m ON m.id = t.marchand_id
            WHERE m.supermarchand_id = " . intval($superMerchantId) . "
            AND t.mobileuser_id = " . intval($mobileUserId) . "
            AND t.`transaction_date` > DATE_SUB( NOW( ) , INTERVAL " . $nbDays . " DAY)";
  
  doLog("Check 15 scans query: " . $query);
  $maxMonthlyTransCount = 15;
  if ($merchandId && $merchandId === 2151) {
    $maxMonthlyTransCount = 25;
  }

  $queryRes = mysql_query($query);
  $moreThan10ScansInAMonth = false;
  if(mysql_num_rows($queryRes)) {
    $resRow = mysql_fetch_array($queryRes);
    $transCount = intval($resRow[0]);
    if($transCount >= $maxMonthlyTransCount) {
      $moreThan10ScansInAMonth = true;
    }
  }
 
  mysql_free_result($queryRes);

  if($moreThan10ScansInAMonth) {
    blockMobileUser($mobileUserId, $superMerchantId);
    return false;
  }

  return true;
}

function isMobileUserBolcked(/*.int.*/ $mobileUserId, /*.int.*/ $merchandId) {
  $queryRes = mysql_query(
    "SELECT COUNT(id) nb FROM marchand_has_mobileuser
     WHERE mobileuser_id = " . intval($mobileUserId) . "
     AND marchand_id = " . intval($merchandId) . "
     AND block_end IS NOT NULL
     AND block_end > NOW()"
  );

  $count = mysql_fetch_array($queryRes);
  mysql_free_result($queryRes);

  return (intval($count[0]) > 0);
}

/**
 * Gets a marchand & mobile user link record
 */
function getMarchandMobileUser(/*.int.*/ $mobileUserId, /*.int.*/ $merchandId) {
  $queryRes = mysql_query("SELECT * FROM marchand_has_mobileuser WHERE mobileuser_id = " . intval($mobileUserId) . " AND marchand_id = " . intval($merchandId));
  if(mysql_num_rows($queryRes)) {
    $res = mysql_fetch_array($queryRes, MYSQL_ASSOC);
    mysql_free_result($queryRes);

    return $res;
  }

  return false;
}
