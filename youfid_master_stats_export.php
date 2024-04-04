<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');

	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");

	$tbl_authentification = "authentification";
	$tbl_marchands = "marchand";
	$tbl_label = "label";

	if (isset($logger))
		$logger->log('debug', 'table_merchant_data', "inFILE", Logger::GRAN_MONTH);

	$result = sort_data();
	
	//////////////////////////// .CSV EXPORT //////////////////////////////////////////
	
	// Nom du fichier final
	$fileName = "marchand-" . date("d") . "_" . date("m") . "_" .date("Y"). ".csv";
	// la variable qui va contenir les données CSV
	$outputCsv = '';
	for ($i = 0; isset($result[$i]); $i += 1)
	{
        //$i++;
        $Row = $result[$i];
        // Si c'est la 1er boucle, on affiche le nom des champs pour avoir un titre pour chaque colonne
        if($i == 0)
        {
            foreach($Row as $clef => $valeur)
                $outputCsv .= trim($clef).';';

            $outputCsv = rtrim($outputCsv, ';');
            $outputCsv .= "\n";
        }

        // On parcours $Row et on ajout chaque valeur à cette ligne
        foreach($Row as $clef => $valeur)
            $outputCsv .= trim($valeur).';';

        // Suppression du ; qui traine à la fin
        $outputCsv = rtrim($outputCsv, ';');

        // Saut de ligne
        $outputCsv .= "\n";

    }
	

	// Entêtes (headers) PHP qui vont bien pour la création d'un fichier Excel CSV
	header("Content-disposition: attachment; filename=".$fileName);
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: application/vnd.ms-excel\n");
	header("Pragma: no-cache");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
	header("Expires: 0");
	
	echo $outputCsv;
	exit();	
	
	//////////////////////////// .CSV EXPORT //////////////////////////////////////////
	
	function sort_data()
	{
		global $logger, $tbl_authentification, $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_marchands WHERE `is_supermarchand`='0'";
		
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		$merchant_array = array();
		while ($row = mysql_fetch_array($result))
		{
			$merchant = array();
			
			if (($categorie = get_merchant_category($row['id'])) && ($nb_scan = get_merchant_nbscan($row['id']) != -1))
			{
				$nb_scan = get_merchant_nbscan($row['id']);
				
				$merchant['id'] = intval($row['id']);
				$merchant['name'] = $row['name'];
				$merchant['zip_code'] = $row['zip_code'];
				$merchant['categorie'] = strval($categorie);
				$merchant['nb_scan'] = $nb_scan;
				
				array_push($merchant_array, $merchant);
			}
		}
		
		return $merchant_array;
	}
	
	/// Get nb_auth by merchant
	function get_merchant_nbscan($merchant_id)
	{
		global $logger, $tbl_authentification, $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_authentification WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return -1;
		
		$nb_auth = 0;
		while ($row = mysql_fetch_array($result))
			$nb_auth += 1;
		
		return $nb_auth;
	}
	
	function get_merchant_category($merchant_id)
	{
		global $logger, $tbl_marchands, $tbl_label;
		
		$sql_get_merchant = "SELECT * FROM $tbl_marchands WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		if (isset($logger))
				$logger->log('debug', 'pie_chart_categorie_data', "inget_merchant_category::query::" . $sql_get_merchant, Logger::GRAN_MONTH);
				
		$result = mysql_query($sql_get_merchant);
		
		if ($result == FALSE)
			return FALSE;
		
		/// Si resultat
		if ($row = mysql_fetch_array($result))
		{
			/// Cas 1: le marchand possede un super_marchand
			if ($row['supermarchand_id'] != -1)
			{
				$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
					. mysql_real_escape_string($row['supermarchand_id'])
					. "'";
					
				$result = mysql_query($query);
				
				if ($result && ($row = mysql_fetch_array($result)))
				{
					$query = "SELECT * FROM $tbl_label WHERE `id`='"
					. mysql_real_escape_string($row['label_id'])
					. "'";
					
					$result = mysql_query($query);
				
					if ($result && ($row = mysql_fetch_array($result)))
					{
						if (strtolower($row['type']) == "categorie")
							return $row['nom'];
					}	
				}
			}
			/// Cas 2: le marchand n'a pas de super_marchand
			else
			{
				$query = "SELECT * FROM $tbl_label WHERE `id`='"
					. mysql_real_escape_string($row['label_id'])
					. "'";
					
				$result = mysql_query($query);
				
				if ($result && ($row = mysql_fetch_array($result)))
				{
					if (strtolower($row['type']) == "categorie")
						return $row['nom'];
				}
			}
		}
		
		return FALSE;
	}

?>