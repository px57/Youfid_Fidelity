<?php

if(function_exists('newrelic_set_appname')) {
	newrelic_set_appname('youfid_prod');
}

require_once '_security.php';
$url_loyalty = "http://localhost:8080/loyalty-1.0/";
define('LOYALTY_BASE_URL', $url_loyalty);

//require_once("Logger.class.php");

function array_map_utf8_encode($array)
{
    if(!is_array($array))
    {
    	return utf8_encode($array);
    }

    foreach($array as $key => $value)
    {
        if(is_array($value))
        {
            $array[$key] = array_map_utf8_encode($value);
        }
        else
        {
            $array[$key] = utf8_encode($value);
        }
    }

    return $array;
}


// TODO : ellaborer le dictionnaire

function generatePassword($length = 8)
{
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$count = mb_strlen($chars);

    for ($i = 0, $result = ''; $i < $length; $i++)
    {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }

	return $result;
}


function distance($lat1, $lon1, $lat2, $lon2)
{
	$lat1 = deg2rad($lat1);
	$lat2 = deg2rad($lat2);
	$lon1 = deg2rad($lon1);
	$lon2 = deg2rad($lon2);

	$R = 6371;
	$dLat = $lat2 - $lat1;
	$dLong = $lon2 - $lon1;
	$var1 = $dLong/2;
	$var2 = $dLat/2;
	$a = pow(sin($dLat/2), 2) + cos($lat1) * cos($lat2) * pow(sin($dLong/2), 2);
	$c = 2 * atan2(sqrt($a),sqrt(1-$a));
	$d = $R * $c;

	return $d;
}

/**
 * 	postRequest(String, String)
 *  Effectue une requete POST et renvoie la reponse de l'appel
 *
 *  @param string $url Path complet du service a appeller
 * 	@param string $param Chaine encodee en Json
 *
 */
function postRequest($url, $param, $async = false) {
	global $logger;

	if (isset($logger))
		$logger->log('debug', 'utils_postrequest', "HTTPPOSTREQUEST::url=" . $url . " :: param=" . $param, Logger::GRAN_MONTH);

	//$url = 'http://api.flickr.com/services/xmlrpc/';

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8","Accept:application/json, text/javascript, */*; q=0.01"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, 'json='.urlencode($param));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);

	if (isset($logger))
		$logger->log('debug', 'utils_postrequest', "HTTPPOSTREQUEST::response=" . $response, Logger::GRAN_MONTH);

	return $response;
}

function get_user_points($user_public_id, $merchant_public_id) {
  $loyaltyRequest = array(
    "wsAccessPublicKey" => 'xxxxxx',
    "wsAccessToken" => 'yyyyyyyyyyyyy',
    "applicationPublicId" => $merchant_public_id,
    "mobileUserPublicId" => $user_public_id
  );

  $get_points_url = LOYALTY_BASE_URL . "services/mobileuser/mobiuserapp";

  $loyRawRes = postRequest($get_points_url, json_encode($loyaltyRequest));

  if(!empty($loyRawRes)) {
    $loyRes = json_decode($loyRawRes);

    if($loyRes->error->code === 0) {
      return intval($loyRes->mobileUserApplication->totalPoints);
    }
	}

	return 0;
}

// Checks if mail is valid
function verifyEmail($mail) {
	$emailCheckerApiKey = '0154D03BB1E97A37';
	$emailCheckerUrl = sprintf(
		'https://api.emailverifyapi.com/api/a/v1?key=%s&email=%s',
		$emailCheckerApiKey,
		$mail
	);

	$ch = curl_init($emailCheckerUrl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-type: application/json'
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);

	$rawResponse = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$response = new stdClass;
	if (intval($httpcode) !== 200 ) {
		// Server error, Ignore validation
		$response->status = 'Ok';
	} else {
		if (isset($logger)) {
			$logger->log('debug', 'utils_mailverification', 'MailVerification::response=' . $rawResponse, Logger::GRAN_MONTH);
		}
	
		if ($rawResponse) {
			$response = json_decode($rawResponse);
			// Key or limit error, Ignore validation
			if (!property_exists($response, 'status')) {
				$response->status = 'OK';
			}
		} else {
			// Server error, Ignore validation
			$response->status = 'OK';
		}
	}

	return $response;
}

/// uuid generator
function gen_uuid() {
	$randomString = openssl_random_pseudo_bytes(16);
  	$time_low = bin2hex(substr($randomString, 0, 4));
  	$time_mid = bin2hex(substr($randomString, 4, 2));
  	$time_hi_and_version = bin2hex(substr($randomString, 6, 2));
  	$clock_seq_hi_and_reserved = bin2hex(substr($randomString, 8, 2));
  	$node = bin2hex(substr($randomString, 10, 6));

  	/**
   	 * Set the four most significant bits (bits 12 through 15) of the
   	 * time_hi_and_version field to the 4-bit version number from
   	 * Section 4.1.3.
  	 * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
  	 */
  	$time_hi_and_version = hexdec($time_hi_and_version);
  	$time_hi_and_version = $time_hi_and_version >> 4;
  	$time_hi_and_version = $time_hi_and_version | 0x4000;

  	/**
   	 * Set the two most significant bits (bits 6 and 7) of the
   	 * clock_seq_hi_and_reserved to zero and one, respectively.
   	 */
  	$clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
  	$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
  	$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

  	return sprintf('%08s-%04s-%04x-%04x-%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
}

function gen_yfid() {
	return sprintf( '%04d%03d',
		mt_rand( 0, 9999 ), mt_rand( 0, 999 )
	);
}

/// Get latitude/longitude from an $address: ex= "22 rue rambuteau, 75003 PARIS, france"

function getXmlCoordsFromAdress($address) {
	$coords=array();
	$base_url="http://maps.googleapis.com/maps/api/geocode/xml?";
	// ajouter &region=FR si ambiguité (lieu de la requete pris par défaut)
	$request_url = $base_url . "address=" . urlencode($address).'&sensor=false';
	$xml = simplexml_load_file($request_url) or die("url not loading");
	//print_r($xml);

	$coords['lat']=$coords['lon']='';
	$coords['status'] = $xml->status ;
	if($coords['status']=='OK')
	{
		$coords['lat'] = $xml->result->geometry->location->lat ;
		$coords['lon'] = $xml->result->geometry->location->lng ;
	}

	return $coords;

}

/// Transforme une date de format::MM/DD/YYYY en YYYY-MM-DD
function sql_date_format($date)
{
	if (strlen($date) < 10)
		return FALSE;

	$year = substr($date, -4);
	$days = substr($date, -7, 2);
	$month = substr($date, -10, 2);

	return $year . '-' . $month . '-' . $days;
}


function mail_youfid($to_email, $to_name, $subject, $message, $template = 'youfid', $context = 'register')
{

	$message = nl2br(stripslashes($message));
	require_once 'phpmailer/class.phpmailer.php';

	$mail = new PHPmailer();
	$mail->IsSMTP();
	$mail->Host='in.mailjet.com';
	$mail->ContentType = "text/html";
	$mail->CharSet = 'UTF-8';
	$mail->SMTPAuth=true;
	$mail->SMTPSecure = 'tls';
	$mail->Port = '587';
	$mail->Username='660264e37569cf0d280ff5d2c848ea5f';
	$mail->Password='a3e218b10800b436e8a88b96c1874ec4';
	// $mail->Username='737f3e0a307ae2f34dfd2afdd1f7324e';
	// $mail->Password='49be4ede23f58d2c3f44973336d2d3c3';
	$mail->SMTPDebug=false;

	$subject = "YouFID - " . $subject; // Titre de l'email
	$mtitle = $subject; // Titre title html
	$mtitlep = $subject; // Titre en-tête H1

	$mbody = $message;
	// Template YouFID
	if(is_file('mailto/email-' . $template . '.php')) {
		require 'mailto/email-' . $template . '.php';
	} else {
		require 'mailto/email.php';
	}

	$mail->From = 'bienvenue@youfid.fr';
	$mail->FromName = 'YouFID Team';
	$mail->AddAddress($to_email, $to_name); // Intégration du nom et prénom du destinataire
	$mail->AddReplyTo('contact@youfid.fr', 'YouFID Team');
	$mail->Subject = $subject;
	$mail->Body = $body_html;
	$mail->AltBody = htmlentities($body_html);

	# $mail->Send(); --> ne fonctionne pas (??)

	if (defined('SUPERMARCHAND_ID') and SUPERMARCHAND_ID > 0) {
		$template = 'supermarchand_' . SUPERMARCHAND_ID;
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
			'" . mysql_real_escape_string(htmlentities($body_html)) . "',
			'" . mysql_real_escape_string($body_html) . "'
		);
    ");

}

if(!function_exists('sendgrid_mail')) {
  function sendgrid_mail($from, $to, $subject, $body_html) {
    $apiKey = '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';

    $payload = [
      'personalizations' => [
        [
          'to' => [ $to ],
          'subject' => $subject
        ]
      ],
      'content' => [
        [ 'type' => 'text/html', 'value' => $body_html ]
      ],
      'from' => $from,
      'reply_to' => $from
    ];

		$json_payload = json_encode($payload);

    ob_start();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $apiKey
    ]);

    $output = curl_exec($ch);
    $info = curl_getinfo($ch);

    print_r($info);

    curl_close($ch);
    ob_end_clean();

    return true;
  }
}

/****************************
 ********* PDO utils ********
 ****************************/

/**
 * Fetch a collection result.
 * 
 * @param $query the query result.
 * @return a collection of objects.
 */
function fetchCollection($query, $fetchMode = PDO::FETCH_OBJ) {
  $objects = array();
  $query->setFetchMode($fetchMode);
  while($object = $query->fetch()) {
    array_push($objects, $object);
	}

  $query->closeCursor();
  return $objects;
}

/**
 * Fetch a unique object.
 * 
 * @param $query the query result.
 * @return an object.
 */
function fetchOne($query, $fetchMode = PDO::FETCH_OBJ) {
  if($query->rowCount() === 1) {
    $query->setFetchMode($fetchMode);
    $object = $query->fetch();
    $query->closeCursor();
    return $object;
  } else if($query->rowCount() === 0) {
    $query->closeCursor();
    return false;
  } else {
    $query->closeCursor();
    throw new Exception("Fetching singleton, but found more than one row");
  }
}

function get_mobile_user_by_qrcode($db, $qrCode, $merchant, $log = false) {
  $rowUser = false;
  if(empty($qrCode)) {
    return $rowUser;
  }

  $query = $db->prepare(
    "SELECT * FROM mobileuser WHERE mobileuser.`qr_code` = :qr_code"
  );

  $query->execute(array(
    "qr_code" => $qrCode
  ));

  if($query->rowCount() === 1) {
		$rowUser = $query->fetch(PDO::FETCH_ASSOC);
		if($log && is_callable($log)) {
			$log("User found by primary code: " . print_r($rowUser, true));
		}

    return $rowUser;
  }

  if(!$merchant || empty($merchant)) {
		if($log && is_callable($log)) {
			$log("Merchand not defined, return false");
		}
    return $rowUser;
  }

  $query = $db->prepare(
    "SELECT m.* FROM `marchand_has_mobileuser` mm
    INNER JOIN `mobileuser` m ON m.id = mm.mobileuser_id
    WHERE `brand_card` = :qr_code AND `marchand_id`= :marchandId"
  );
 
  $query->execute(array(
    'qr_code' => $qrCode,
    'marchandId' => $merchant['id']
  ));

  if($query->rowCount() === 1) {
		$rowUser = $query->fetch(PDO::FETCH_ASSOC);
		if($log && is_callable($log)) {
			$log("User found by brand code (merc): " . print_r($rowUser, true));
		}

    return $rowUser;
  }

  if(!empty($merchant['supermarchand_id'])) {
    $query = $db->prepare(
      "SELECT m.* FROM `marchand_has_mobileuser` mm
      INNER JOIN `mobileuser` m ON m.id = mm.mobileuser_id
      WHERE `brand_card` = :qr_code AND `marchand_id`= :marchandId"
    );
   
    $query->execute(array(
      'qr_code' => $qrCode,
      'marchandId' => $merchant['id']
    ));
  
    if($query->rowCount() === 1) {
      $rowUser = $query->fetch(PDO::FETCH_ASSOC);
      if($log && is_callable($log)) {
				$log("User found by brand code (super): " . print_r($rowUser, true));
			}
  
      return $rowUser;
    }
  }

  // doLog("User with QR $qrCode not found ");
  return false;
}

/* ***********************
 * Secutity functions 
 *************************/

// Session key lenght
define('SEC_SESSION_KEY_LENGHT', 64);
define('SEC_SIGN_ALGO', 'sha256');
define('CLIENT_ID', 'fid_client_id');
define('NONCE', 'nonce');
define('TSTMP', 'timestamp');
define('TSTMP_TOLERANCE', 60); // 10s
define('SIGN', 'sign');

function getRandomString($length = 8) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = '';

	for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
	}

	return $string;
}

function generateSessionKey($length = SEC_SESSION_KEY_LENGHT) {
	return bin2hex(getRandomString($length));
}

/**
 * HMAC sign
 * 
 * @param $data data to sign in raw bytes array
 * @param $key signing key in hex format
 * @return signature in hex format
 */
function hmacSign($data, $key) {
	$binKey = hex2bin($key);
	$hmac_sign = hash_hmac(SEC_SIGN_ALGO, $data, $binKey);
	return $hmac_sign;
}

/**
 * HMAC verify
 * 
 * @param $data signed data
 * @param $key signing key in hex format
 * @param $signature the signature to verify in hex format
 * @return true if verified, false otherwise
 */
function hmacVerify($data, $key, $signature) {
	$hmac_sign = hmacSign($data, $key);
	$granted = $hmac_sign === $signature;
	$res = new stdClass();
	$res->granted = $granted;
	$res->error = null;

	if(!$granted) {
		$res->error = 'Sign not match: received => ' . $signature . ', computed => ' . $hmac_sign . ', Data => ' . $data . ', key => ' . hex2bin($key);
	}

	return $res;
}

/**
 * Initialize a new session for merchant
 * 
 * @param $dbh PDO database handler
 * @param $merchantId the merchant id
 * @param $sessionKey the new session key.
 * @return the new session key.
 */
function initSession($dbh, $merchantId) {
	$sessionKey = generateSessionKey();

	$query = $dbh->prepare('UPDATE marchand SET session_key = :sessionKey WHERE id = :id');
	$query->execute(array(
		'sessionKey' => $sessionKey,
		'id' => $merchantId
	));
}

/**
 * Get array of auth infos
 * @param $headers request headers
 * @return array of auth info
 */
function getAuthInfos($headers) {
	if(empty($headers) || empty($headers['Authorization'])) {
		return false;
	}

	$authInfos = array();

	$authString = $headers['Authorization'];

	$infoParts = explode(',', $authString);
	foreach($infoParts as $part) {
		$infos = explode('=', $part);
		if(count($infos) === 2) {
			$authInfos[$infos[0]] = $infos[1];
		} else {
			$authInfos[$infos[0]] = '';
		}
	}

	return $authInfos;
}

/**
 * Gets the merchant session key.
 * @param $dbh The PDO DB handler
 */
function getSecurityInfos($dbh, $merchantId) {
	if(!$merchantId || empty($merchantId)) {
		return false;
	}

	$query = $dbh->prepare('SELECT sec_type, session_key FROM marchand WHERE id = :id');
	$query->execute(array(
		'id' => $merchantId
	));

	if($query->rowCount() === 1) {
		$query->setFetchMode(PDO::FETCH_OBJ);
    $secInfos = $query->fetch();
	} else {
		return false;
	}

	$query->closeCursor();
	return $secInfos;
}

/**
 * Get data to be signed (verified)
 * @param $requestData HTTP request data (body or query string)
 * @param $authInfos authorization infos
 * @return the signed data
 */
function getSignedData($requestData, $authInfos) {
	return $requestData.$authInfos[CLIENT_ID].$authInfos[NONCE].$authInfos[TSTMP];
}

/**
 * Test HTTPS connection
 */
function isSecure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| $_SERVER['SERVER_PORT'] == 443;
}

/**
 * Checks if timestamp is valid (not too old).
 */
function isValidTimestamp($timestamp) {
	$currentTimestamp = time();
	return abs($currentTimestamp - $timestamp) <= TSTMP_TOLERANCE;
}

/**
 * Checks wehter request is legitimate or not.
 * @param $dbh PDO handler
 * @param $merchantId merchand id.
 * @param $requestHeaders request headers
 * @param $requestBody request raw body
 * @param $queryString query string (optional)
 */
function checkRequestAuthorization($dbh, $requestHeaders, $requestBody, $queryString = null) {
	$authorization = new stdClass();
/*
	if(!isSecure()) {
		$authorization->merchantId = null;
		$authorization->granted = false;
		$authorization->error = 'Channel not secure';
		return $authorization;
	}
*/
	$authInfos = getAuthInfos($requestHeaders);
	// print_r($authInfos);
	if(!$authInfos || count($authInfos) === 0 || empty($authInfos[CLIENT_ID]) 
		|| empty($authInfos[NONCE]) || empty($authInfos[TSTMP]) || empty($authInfos[SIGN])) {
		$authorization->merchantId = null;
		$authorization->granted = false;
		$authorization->error = 'Missing auth infos: ' . print_r($authInfos, true);
		return $authorization;
	}

	if(!isValidTimestamp($authInfos[TSTMP])) {
		$authorization->merchantId = null;
		$authorization->granted = false;
		$authorization->error = 'Invalid timestamp';
		return $authorization;
	}

	$data = '';
	if(!empty($requestBody)) {
		$data .= $requestBody;
	}

	if(!empty($queryString)) {
		$data .= $queryString;
	}

	$merchantId = $authInfos[CLIENT_ID];

	$secInfos = getSecurityInfos($dbh, $merchantId);

	if(!$secInfos) {
		$authorization->merchantId = null;
		$authorization->granted = false;
		$authorization->error = 'Security not enabled on merchant';
		return $authorization;
	}

	$data = getSignedData($data, $authInfos);
	$res = hmacVerify($data, $secInfos->session_key, $authInfos[SIGN]);

	$authorization->merchantId = $merchantId;
	$authorization->granted = $res->granted;
	$authorization->error = $res->error;

	return $authorization;
}

function isSecurityActivated($merchant) {
	return isset($merchant) && isset($merchant['sec_type']) && $merchant['sec_type'] !== 'none';
}
