#!/usr/bin/php -q
<?php

exit;

error_reporting(E_STRICT);
require_once "System/Daemon.php";                 // Include the Class
require_once "phpmailer/class.phpmailer.php";
//require_once "mailto/email.php"; // Template YouFID

define('DB_HOST','db.youfid.fr');
define('DB_PORT','3306');
define('DB_NAME','youfid');
define('DB_USER','youfid');
define('DB_PASS','youfid');

function send_mail($receiver_email, $receiver_name, $subject, $body)
{
	$message = nl2br(stripslashes($body));

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

	// Titre de l'email
	$subject = "YouFID - " . $subject;
    if($body == 'sinequanone')
		$subject = "Sinequanone - " . $subject;
	if(strstr($body, "Commerces de"))
		$subject = "Les Commerces de l'Arche - " . $subject;

	$msg_title = $subject; // Titre title html
	$msg_h1 = $subject; // Titre en-tête H1

    $mbody = $message;

    include("mailto/email.php"); // Template YouFID Standard
    if($body == 'sinequanone')
    	include("mailto/email-sinequanone.php");

    //System_Daemon::info($template);

	$msg_unsubscribe = "Vous recevez ce message car vous vous êtes inscrit avec l'email ".$receiver_email.""; // Pied du message avec l'email

	if(strstr($body, "Commerces de")) {
		$mail->From='bienvenue@youfid.fr';
		$mail->FromName = "Les Commerces de l'Arche";
		$mail->AddReplyTo ('contact@youfid.com', "Les Commerces de l'Arche");
	} else {
		$mail->From='bienvenue@youfid.fr';
		$mail->FromName = 'YouFID Team';
		$mail->AddReplyTo ('contact@youfid.com', 'YouFID Team');
	}

	$mail->AddAddress($receiver_email, $receiver_name); // Intégration du nom et prénom du destinataire
	$mail->Subject=$subject;
    $mail->Body=$body_html;
    $mail->AltBody=htmlentities($body_html);

	if(!$mail->Send()){
		return array(
			"Status" => "NOK",
			"Error" => $mail->ErrorInfo
		);
	} else {
		return array(
			"Status" => "OK",
			"Error" => ""
		);
	}
}

// Setup
$options = array(
    'appName' => 'youfidmailer',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Sends mails in youfid database',
    'authorName' => 'Abdoul DIALLO',
    'authorEmail' => 'abdoulsalamy@yahoo.fr',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
//    'appRunAsGID' => 1000,
//    'appRunAsUID' => 1000,
);

System_Daemon::setOptions($options);
System_Daemon::start(); // Spawn Deamon!

while(true)
{
	$dbh = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);

	$mailsStmt = $dbh->query("
		SELECT *
		FROM  sendmail
		WHERE status LIKE 'WAITING'
		LIMIT 100
	");

	while($mail = $mailsStmt->fetch(PDO::FETCH_OBJ))
	{
		// Send mail
		$res = send_mail($mail->receiver_email, $mail->receiver_name, $mail->subject, $mail->message);
		if($res["Status"] == "OK") {
			System_Daemon::info("Message sent successfully");
			$del_stmt = $dbh->prepare("
				DELETE FROM sendmail
				WHERE id = :id
			");
			$del_stmt->execute(array("id" => $mail->id));
		}
		else
		{
			System_Daemon::err("Could not send mail");
			$update_stmt = $dbh->prepare("
				UPDATE sendmail
				SET
					status = 'FAILED',
					error = :error
				WHERE id = :id
			");
			$update_stmt->execute(array(
				"error" => $res["Error"],
				"id" => $mail->id
			));

		}
	}

	$mailsStmt->closeCursor(); // Close statement cursor
	$dbh = null; // Close connection

	System_Daemon::iterate(10);

}

System_Daemon::stop();


