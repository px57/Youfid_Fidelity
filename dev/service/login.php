<?php
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die();
  }

  require_once('Logger.class.php');
  require_once 'utils.php';
  if (!isset($logger))
    $logger = new Logger('logs/');

  function doLog($message)
  {
    global $logger;

    if (isset($logger))
      $logger->log('debug', 'login', $message, Logger::GRAN_MONTH);
  }

  /// table name
  $tbl_name="mobileuser";
  require_once('dbLogInfo.php');

  $json = file_get_contents('php://input');
  $jsonArray = json_decode($json);

  doLog("REQUEST::" . $json);

  $error = false;
  $errorMsg = "";

  /// Pour le cas ou un user Facebook n'est pas encore inscrit en BDD
  $register = false;
  $customerArray = array();

  /// App Cliente
  if (isset($jsonArray->source) && $jsonArray->source == 'youfid')
  {
    if (!isset($jsonArray->email) || !isset($jsonArray->password))
    {
      $error = true;
      $errorMsg = "Error, some parameters who are mandatory are missing...";
    }
    else
    {
      mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
      mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

      /*$sqlApp = "SELECT * FROM $tbl_name WHERE `mail` = '"
        . mysql_real_escape_string($jsonArray->email)
        . "' AND `password` = '"
        . mysql_real_escape_string($jsonArray->password)
        . "'";*/
      $sqlApp = "SELECT * FROM $tbl_name WHERE `mail` = '"
        . mysql_real_escape_string($jsonArray->email)
        . "' AND `password` = PASSWORD('"
        . mysql_real_escape_string($jsonArray->password)
        . "')";

      $result = mysql_query($sqlApp);

      if ($result == FALSE)
      {
        $error = TRUE;
        $errorMsg = "Error with the DataBase.";
      }
      else
      {

        if (mysql_num_rows($result))
        {
          $customerRow = mysql_fetch_array($result);
          /*$test = $customerRow['status'];
          if ($test == 2){
            $testInscri = "SELECT * FROM $tbl_name WHERE id '"
                  . mysql_real_escape_string($customerRow['id'])
                  . "' AND date_inscription > DATE_SUB(NOW(), INTERVAL 15 DAY)";
            $resultTest = mysql_query($testInscri);
            if (!(mysql_num_rows($resultTest))) {
              $test = 0;
            }
          }

          if ($test) {*/
            $customerArray['usr_id'] = $customerRow['id'];
            $customerArray['first_name'] = $customerRow['prenom'];
            $customerArray['last_name'] = $customerRow['nom'];
            $customerArray['email'] = $customerRow['mail'];
            $customerArray['qr_code'] = $customerRow['qr_code'];
            $customerArray['is_pin_active'] = $customerRow['is_pin_active'];
            $customerArray['pin_code'] = $customerRow['pin_code'];

            if(!$customerRow['birthdate']) { $customerRow['birthdate'] = '2000-01-01'; }
            $customerArray['birthdate'] = date('Y-m-d', strtotime($customerRow['birthdate']));
          /*}
          else {
            $error = TRUE;
            $errorMsg = "Error: email validation need to be done";
          }*/
        }
        else
        {
          $error = TRUE;
          $errorMsg = "Error: bad user combinaison.";
        }
      }
    }
  }
  else if (isset($jsonArray->source) && $jsonArray->source == 'facebook')
  {
    if (!isset($jsonArray->fb_id) || !isset($jsonArray->token))
    {
      $error = true;
      $errorMsg = "Error, some parameters who are mandatory are missing...";
    }
    else
    {
      mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
      mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

      $sqlFacebook = "SELECT * FROM $tbl_name WHERE `fb_id` = '"
        . mysql_real_escape_string($jsonArray->fb_id)
        . "'";

      $result = mysql_query($sqlFacebook);
      if ($result == FALSE)
      {
        $error = TRUE;
        $errorMsg = "Error with the DataBase.";
      }
      else
      {
        if (mysql_num_rows($result))
        {
          $customerRow = mysql_fetch_array($result);

          $customerArray['usr_id'] = $customerRow['id'];
          $customerArray['first_name'] = $customerRow['prenom'];
          $customerArray['last_name'] = $customerRow['nom'];
          $customerArray['email'] = $customerRow['mail'];
          $customerArray['qr_code'] = $customerRow['qr_code'];
          //$customerArray['picture'] = $customerRow['photo'];
          $customerArray['is_pin_active'] = $customerRow['is_pin_active'];
          $customerArray['pin_code'] = $customerRow['pin_code'];
          $customerArray['birthdate'] = $customerRow['birthdate'];

          /// Mise a jour du token en BDD
          $sqlFacebookUpdate = "UPDATE $tbl_name SET `token`='"
            . mysql_real_escape_string($jsonArray->token)
            . "' WHERE `id`='"
            . mysql_real_escape_string($customerRow['id'])
            . "'";

          $result = mysql_query($sqlFacebookUpdate);
          if ($result == FALSE)
          {
            $error = TRUE;
            $errorMsg = "Error during fb_token UPDATE";
          }
        }
        else
        {
          $register = true;
        }
      }
    }
  }
  else if (isset($jsonArray->source) && $jsonArray->source == 'logout')
  {
    if (!isset($jsonArray->usr_id))
    {
      $error = true;
      $errorMsg = "Error, some parameters who are mandatory are missing...";
    }
    else
    {
      mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
      mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

      $sqlLogin = "SELECT * FROM $tbl_name WHERE `id` = '"
        . mysql_real_escape_string($jsonArray->usr_id)
        . "'";

      $result = mysql_query($sqlLogin);
      if ($result == FALSE)
      {
        $error = TRUE;
        $errorMsg = "Error with the DataBase.";
      }
      else
      {
        if (!mysql_num_rows($result))
        {
          $error = TRUE;
          $errorMsg = "Error: bad user combinaison.";
        }
      }
    }
  }
  else
  {
    $error = TRUE;
    $errorMsg = "Error: Some parameters who are mandatory are missing.";
  }

  $jsonResult = array();

  if ($error == TRUE)
  {
    $jsonResult['status'] = "error";
    $jsonResult['message'] = $errorMsg;
    yf_security_log_event('authko');
  }
  else if ($register == TRUE)
  {
    $jsonResult['status'] = "register";
    yf_security_log_event('register');
  }
  else
  {
    $jsonResult = $customerArray;
    $jsonResult['status'] = "ok";
    yf_security_log_event('authok');
  }

  echo(json_encode(array_map_utf8_encode($jsonResult)));
  doLog("RESPONSE::" . json_encode(array_map_utf8_encode($jsonResult)));

