<?php

	$real_path = 'http://' . $_SERVER['SERVER_NAME'] . '/dynamic/user_logos/';
	$path = '../dynamic/user_logos/';

	$logger->log('debug', 'commerciaux_register_marchand', "FILETRANSFER:realpath=" . $real_path, Logger::GRAN_MONTH);

	$is_valid = TRUE;

	/// Un path est renseigné
	if (isset($_FILES['logopath']['error']) && $_FILES['logopath']['error'] == 0)
	{
		$maxsize = 1048576;
		
		$logger->log('debug', 'commerciaux_register_marchand', "FILETRANSFER:in", Logger::GRAN_MONTH);
		
		/// Get du fichier
		$extensions_valides = array( 'jpg' , 'jpeg' , 'png', 'gif' );
		//1. strrchr renvoie l'extension avec le point (« . »).
		//2. substr(chaine,1) ignore le premier caractère de chaine.
		//3. strtolower met l'extension en minuscules.
		$extension_upload = strtolower(  substr(  strrchr($_FILES['logopath']['name'], '.')  ,1)  );
		if (!in_array($extension_upload,$extensions_valides))
			$is_valid = FALSE;
		if ($_FILES['logopath']['size'] > $maxsize)
			$is_valid = FALSE;
		
		if ($is_valid == TRUE)
		{
			mkdir($path, 0777, true);
			
			$nom = md5(uniqid(rand(), true));
			$nom = $nom . '.' . $extension_upload;
			$resultat = move_uploaded_file($_FILES['logopath']['tmp_name'], $path . $nom);
			
			/// Sauvegarde du path
			if ($resultat)
				$logo = $real_path . $nom;
		}
	}
	/// Une URL est renseignée
	else if (isset($_POST['logourl']) && !empty($_POST['logourl']))
	{
		$maxsize = 1048576;
		$url = $_POST['logourl'];
		$extension = substr($url, -3, 3);
		
		$extension_upload = strtolower($extension);
		$extensions_valides = array( 'jpg' , 'jpeg' , 'png', 'gif' );
		
		if (!in_array($extension_upload,$extensions_valides))
			$is_valid = FALSE;
		if ($_FILES['logopath']['size'] > $maxsize)
			$is_valid = FALSE; 
		
		if ($is_valid == TRUE)
		{
			mkdir($path, 0777, true);
			
			$nom = md5(uniqid(rand(), true));
			$nom = $nom . '.' . $extension_upload;
			
			$content = file_get_contents($_POST['logourl']);
			$resultat = file_put_contents($path . $nom, $content);
			
			$logger->log('debug', 'commerciaux_register_marchand', "URLTRANSFER:name=" . $nom, Logger::GRAN_MONTH);
			
			if ($resultat)
				$logo = $real_path . $nom;
		}
	}
			
		/*// On genere une miniature
			if ($resultat)
			{
				$logger->log('debug', 'commerciaux_register_marchand', "FILETRANSFER:SUCCESS", Logger::GRAN_MONTH);
				
				$fichierSource = $path . $nom;
     
			    $largeurDestination = 200; 
			    $hauteurDestination = 150; 
			    $im = ImageCreateTrueColor ($largeurDestination, $hauteurDestination)  
			            or die ("Erreur lors de la création de l'image");  
			
			    $source = ImageCreateFromJpeg($fichierSource); 
			     
			    $largeurSource = imagesx($source); 
			    $hauteurSource = imagesy($source);
			    
			    $blanc = ImageColorAllocate ($im, 255, 255, 255); 
			    $gris[0] = ImageColorAllocate ($im, 90, 90, 90);  
			    $gris[1] = ImageColorAllocate ($im, 110, 110, 110);         
			    $gris[2] = ImageColorAllocate ($im, 130, 130, 130);  
			    $gris[3] = ImageColorAllocate ($im, 150, 150, 150);  
			    $gris[4] = ImageColorAllocate ($im, 170, 170, 170);  
			    $gris[5] = ImageColorAllocate ($im, 190, 190, 190);  
			    $gris[6] = ImageColorAllocate ($im, 210, 210, 210);  
			    $gris[7] = ImageColorAllocate ($im, 230, 230, 230);  
			
			    for ($i=0; $i<=7; $i++) { 
			        ImageFilledRectangle ($im, $i, $i, $largeurDestination-$i, $hauteurDestination-$i, $gris[$i]);     
			    }
				
				ImageCopyResampled($im, $source, 8, 8, 0, 0, $largeurDestination-(2*8), $hauteurDestination-(2*8), $largeurSource, $hauteurSource); 
     
    			ImageString($im, 0, 12, $hauteurDestination-18, "$fichierSource - ($largeurSource x $hauteurSource)", $blanc);
				$miniature = $path . "mini_" . $nom; 
    			$result = ImageJpeg ($im, $miniature);
				
				$logo_mini = $real_path . "mini_" . $nom;
				$logger->log('debug', 'commerciaux_register_marchand', "FILETRANSFER:MINIATURE=" . $result, Logger::GRAN_MONTH);
    			//echo "Image miniature générée: $miniature"; 
			}
			else
				$logger->log('debug', 'commerciaux_register_marchand', "FILETRANSFER:FAIL", Logger::GRAN_MONTH);*/
		
		
?>