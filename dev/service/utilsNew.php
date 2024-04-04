<?php
	$url_loyalty = "http://localhost:8080/loyalty-1.0/";

	//require_once("Logger.class.php");

	// TODO : ellaborer le dictionnaire

	function generatePassword($length = 8)
	{
    	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    	$count = mb_strlen($chars);

	    for ($i = 0, $result = ''; $i < $length; $i++) {
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
		$var1= $dLong/2;
		$var2= $dLat/2;
		$a= pow(sin($dLat/2), 2) + cos($lat1) * cos($lat2) * pow(sin($dLong/2), 2);
		$c= 2 * atan2(sqrt($a),sqrt(1-$a));
		$d= $R * $c;
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

	function postRequest($url, $param)
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

	/// uuid generator
	function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
	}

	/*function gen_yfid() {
    return sprintf( '%04d%04d%04d',
        mt_rand( 0, 9999 ), mt_rand( 0, 9999 ), mt_rand( 0, 9999 )
    );
	}*/

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

function mail_youfid($to, $subject, $body)
	{
		$message = nl2br(stripslashes($body));

		require('phpmailer/class.phpmailer.php');

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

		$mail->From='admin@youfid.com';
		$mail->FromName = 'YouFID Team';
		$mail->AddAddress($to, 'Utilisateur YouFID');
		$mail->AddReplyTo ('contact@youfid.com', 'YouFID Team');
		$mail->Subject=$subject;
		$mail->Body='<p>'.$message.'</p><p><em>(Action effectu&eacute;e &agrave; partir de l\'application YouFID, le '.date("d-m-Y   H:i:s").')</em></p>';
		$mail->AltBody=htmlentities($message);

		if(!$mail->Send()){
     		$error =  '<p class="error">Nous avons rencontr&eacute; un probl&egrave;me lors de l\'envoi de votre message.</p>';
		} else {
    	 	$error = '<p class="success">Votre message a bien &eacute;t&eacute; envoy&eacute;!</p>';
		}
	}

/*
	function mail_youfid($email, $to, $subject, $body)
	{

		$message = nl2br(stripslashes($body));

		require('phpmailer/class.phpmailer.php');
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

		$subject = "YouFID - ".$subject; // Titre de l'email
		$msg_title = $subject; // Titre title html
		$msg_h1 = $subject; // Titre en-tête H1


		$msg_body = "<p>Bonjour ".$to.",</p>"; // Corps du message
		$msg_body .= $message;
		$msg_body .= " <p>Cordialement, <br> L'équipe YouFID</p>";


		$msg_unsubscribe = "Vous recevez ce message car vous vous êtes inscrit avec l'email ".$email.""; // Pied du message avec l'email

		require_once('mailto/email.php'); // Template YouFID

		$mail->From='bienvenue@youfid.fr';
		$mail->FromName = 'YouFID Team';
		$mail->AddAddress($email, $to); // Intégration du nom et prénom du destinataire
		$mail->AddReplyTo ('contact@youfid.com', 'YouFID Team');
		$mail->Subject=$subject;
		$mail->Body=$msg;
		$mail->AltBody=htmlentities($msg);

		if(!$mail->Send()){
    		$error =  '<p class="error">Nous avons rencontr&eacute; un probl&egrave;me lors de l\'envoi de votre message.</p>';
		} else {
   	 	$error = '<p class="success">Votre message a bien &eacute;t&eacute; envoy&eacute;!</p>';
		}
	}
*/

