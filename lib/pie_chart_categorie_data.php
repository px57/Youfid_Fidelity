<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
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
		$logger->log('debug', 'pie_chart_categorie_data', "inFILE", Logger::GRAN_MONTH);

	$result = sort_data();
	
	if ($result)
		$json_result = json_encode($result);
	else
		$json_result = "{}";
	
	if (isset($logger))
		$logger->log('debug', 'pie_chart_categorie_data', "Response::" . $json_result , Logger::GRAN_MONTH);
	
	echo($json_result);

	function sort_data()
	{
		global $logger, $tbl_authentification, $tbl_marchands;
		
		$query = "SELECT * FROM $tbl_authentification";
		$result = mysql_query($query);
		
		if ($result == FALSE)
			return FALSE;
		
		/// un premier tableau de type merchant_id => nb_auth
		$id_array = array();
		while ($row = mysql_fetch_array($result))
		{
			if (array_key_exists($row['marchand_id'], $id_array))
				$id_array[$row['marchand_id']] += 1;
			else
				$id_array[$row['marchand_id']] = 1;
		}
		
		/// Si le tableau est vide, on stoppe pour eviter des requetes inutiles
		if (!$id_array)
			return FALSE;
		
		$sort_array = array();
		/// id_merchant::KEY value::VALUE
		/// Sort des marchand sous la forme : categorie => nb_autent
		foreach ($id_array as $id_merchant => &$value )
		{
		    if ($categorie = get_merchant_category($id_merchant))
		    {
		    	if (isset($logger))
					$logger->log('debug', 'pie_chart_categorie_data', "insort_array::category::" . $categorie, Logger::GRAN_MONTH);
		    	if (array_key_exists($categorie, $sort_array))
					$sort_array[$categorie] += $value;
				else
					$sort_array[$categorie] = $value;
		    }
		}
		
		/// tri du tableau par auth croissante
		$sort_array_auth = array();
		foreach ($sort_array as $name => &$value)
			array_push($sort_array_auth, $value);
		array_multisort($sort_array_auth, SORT_DESC, $sort_array);
		
		/// On selectionne un maximum de 10 marchands
		$top_ten = array();
		//$array_push($top_ten, array("Franchise" => "Nombre d'auth"));
		//$top_ten['Franchise'] = "Nombre d'auth";
		$index = 0;
		foreach($sort_array as $name => &$value)
		{
			if ($index < 10)
				array_push($top_ten, array($name => $value));
			$index += 1;
		}
		
		$dataTable = array(
  			'cols' => array(
	         	// each column needs an entry here, like this:
	         	array("id"=>"", "label" => "Franchise", "pattern"=>"", "type" => "string"), 
	         	array("id"=>"", "label" => "Authentifications", "pattern"=>"", "type" => "number")
    		)
		);
		
		foreach($sort_array as $name => &$value)
		{
			$dataTable['rows'][] = array(
				'c' => array (
	            	array('v' => $name, 'f' => null), 
	            	array('v' => $value, 'f' => null)
         		)
    		);
		}
		
		if (isset($logger))
			$logger->log('debug', 'pie_chart_categorie_data', "insort_data::return::" . json_encode($dataTable), Logger::GRAN_MONTH);
		
		//return($top_ten);
		return $dataTable;
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
