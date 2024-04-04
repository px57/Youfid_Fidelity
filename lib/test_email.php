<?php

	function mail_youfid($to, $subject, $body)
	{
		$message.= "
			<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
			<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">
			<head>
				<title>inscription</title>
				<meta http-equiv=\"Content-Type\" content=\"text/HTML; charset=utf-8\" />
			</head>
			<body>";
			
		$message.= nl2br(stripslashes($body));
		$message.="</body></html>";
		
		$headers  = 'MIME-Version: 1.0' . "\r\n";
	  	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	   	$headers .= 'From: AdminYoufid <admin@youfid.fr>' . "\r\n";
		
		mail($to, $subject, $message, $headers);
	}

	$to  = 'pierre.rodier@gmail.com' . ', '; // notez la virgule
	$to .= 'alexandre.crouan@4gsecure.com';

	if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $to))
	{
	    $passage_ligne = "\r\n";
	}
	else
	{
	    $passage_ligne = "\n";
	}

	// Plusieurs destinataires
    $to  = 'pierre.rodier@gmail.com' . ', '; // notez la virgule
	$to .= 'alexandre.crouan@4gsecure.com';
     
     // Sujet
     $subject = 'Calendrier des anniversaires pour Août';

	$message.= "
		<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">
		<head>
		<title>inscription</title>
		<meta http-equiv=\"Content-Type\" content=\"text/HTML; charset=utf-8\" />
		</head>
		<body>";

	$body = "<p>L'equipe Youfid est fière de vous donner votre super mot de passe: C'est gagné gagné!!! Août Août Août</p></br></br>";
	$body .= "<p>Login:toto@gmail.com</p></br>";
	$body .= "<p>Password:8edsd455d12</p></br></br>";
	$body .= "<p>L'equipe YouFid</p></br></br>";

	$message.= nl2br(stripslashes($body));
	$message.="</body></html>";
    /* // message
     $message = '
	 	<html>
	    	<head>
	       		<title>Calendrier des anniversaires pour Août</title>
	      	</head>
	      	<body>
	       		<p>Voici les anniversaires à venir au mois d\'Août !</p>
	       		<table>
		        	<tr>
		         		<th>Personne</th><th>Jour</th><th>Mois</th><th>Année</th>
		        	</tr>
		        	<tr>
		         		<td>Josiane</td><td>3</td><td>Août</td><td>1970</td>
		        	</tr>
		        	<tr>
		         		<td>Emma</td><td>26</td><td>Août</td><td>1973</td>
		        	</tr>
	       		</table>
	       		<p> L\'équipe YouFid</p>
	      	</body>
	  	</html>
	  	';*/

	//$message = nl2br(stripslashes($message));

     // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
     $headers  = 'MIME-Version: 1.0' . "\r\n";
     $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

     // En-têtes additionnels
     $headers .= 'From: AdminYoufid <admin@youfid.fr>' . "\r\n";

     // Envoi
     mail($to, $subject, $message, $headers);
     
    /*//=====Déclaration des messages au format texte et au format HTML.
	$message_txt = "Salut à tous, voici un e-mail envoyé par l'équipe de Youfid. Amusez vous bien avec Youfid!";
	$message_html = "<html><head></head><body><b>Ceci est un email de test</b>, voici un e-mail envoyé par <i>l'equipe de Youfid</i>. Amusez vous bien avec Youfid!</body></html>";
	//==========
     
    //=====Création de la boundary
	$boundary = "-----=".md5(rand());
	//==========
     
    //=====Définition du sujet.
	$sujet = "Hey mon ami !";
	//=========
     
    //=====Création du header de l'e-mail
	$header = "From: \"Admin Youfid\"<admin@youfid.fr>".$passage_ligne;
	//$header .= "Reply-to: \"WeaponsB\" <weaponsb@mail.fr>".$passage_ligne;
	$header .= "MIME-Version: 1.0".$passage_ligne;
	$header .= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
	//==========
	
	//=====Création du message.
	$message = $passage_ligne."--".$boundary.$passage_ligne;
	//$message = "...";
	$message .= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
	$message .= "Content-Transfer-Encoding: 8bit".$passage_ligne;
	$message.= $passage_ligne.$message_txt.$passage_ligne;
	//==========
	
	$message.= $passage_ligne."--".$boundary.$passage_ligne;
	//=====Ajout du message au format HTML
	$message.= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
	$message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
	$message.= $passage_ligne.$message_html.$passage_ligne;
	//==========
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	//==========

	//=====Envoi de l'e-mail.
	mail($to,$sujet,$message,$header);
	//==========*/
?>