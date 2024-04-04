<?php

if(function_exists('newrelic_set_appname')) {
	newrelic_set_appname('youfid_prod');
}


require_once '_security.php';
$url_loyalty = "http://localhost:8080/loyalty-1.0/";

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

function postRequest($url, $param, $async = false)
{
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

// Checks if mail is valid
function verifyEmail($mail) {
    return (object) array(
      "status" => "Ok"
    );

    /*
	$emailCheckerApiKey = "1552301B1F5CC459";
	$emailCheckerUrl = sprintf(
		"https://api.emailverifyapi.com/api/a/v1?key=%s&email=%s",
		$emailCheckerApiKey,
		$mail
	);
	
	$ch = curl_init($emailCheckerUrl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Accept: application/json"
	));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$rawResponse = curl_exec($ch);
	curl_close($ch);

	if (isset($logger)) {
		$logger->log('debug', 'utils_mailverification', "MailVerification::response=" . $rawResponse, Logger::GRAN_MONTH);
	}

	if ($rawResponse) {
		$response = json_decode($rawResponse);
		return $response;
	} else {
		return array(
			"status" => "Error: empty response"
		);
	}*/
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

function gen_yfid()
{
    return sprintf( '%04d%03d',
        mt_rand( 0, 9999 ), mt_rand( 0, 999 )
    );
}

/// Get latitude/longitude from an $address: ex= "22 rue rambuteau, 75003 PARIS, france"

function getXmlCoordsFromAdress($address)
{
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
	$mail->Username='737f3e0a307ae2f34dfd2afdd1f7324e';
	$mail->Password='49be4ede23f58d2c3f44973336d2d3c3';
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
