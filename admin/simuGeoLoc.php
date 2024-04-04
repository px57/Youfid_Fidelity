<?php

/*
  simuGeoLoc.php?&address=152+rue+de+picpus+75012+Paris&rayon=10
*/

// ------------------------------------------
// converts a string with a stret address
// into a couple of lat, long coordinates.
// ------------------------------------------
function getLatLong($_address)
{
    if (!is_string($_address))echo("All Addresses must be passed as a string");
    $_url = sprintf('http://maps.google.com/maps?output=js&q=%s',rawurlencode($_address));
    $_result = false;
    if($_result = file_get_contents($_url)) {
        if(strpos($_result,'errortips') > 1 || strpos($_result,'Did you mean:') !== false) return false;
        preg_match('!center:\s*{lat:\s*(-?\d+\.\d+),lng:\s*(-?\d+\.\d+)}!U', $_result, $_match);
        $_coords['lat'] = $_match[1];
        $_coords['long'] = $_match[2];
    }
    return $_coords;
}

// ------------------------------------------
// calculates distance(meters) between points
// A(lat1, lon1) and B(lat2, lon2)
// ------------------------------------------
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

if (isset($_GET['rue']) && isset($_GET['postcode']) && isset($_GET['ville']) && isset($_GET['pays']) && isset($_GET['rayon'])) 
{
	$rayon = $_GET['rayon'];
	$address = $_GET['rue'].", ".$_GET['postcode']." ".$_GET['ville'].", ".$_GET['pays'];
	echo '<img src="http://www.youfid.fr/Content/logo.png"> </br> </br>';
	echo "Calcul du nombre d'utilisateurs ayant active la geolocalisation dans un rayon de ".$rayon." metres autour du ".$address.":</br></br>";
	$coordinates = getLatLong($address);
	if(!$coordinates)
	{
		echo("Adresse invalide </br>");
		echo("<a href='./simuGeoLoc.php'> Retour </a>");
	}
	else
	{
		$lat = $coordinates['lat'];
		$long = $coordinates['long'];
		
		// echo "Calcul du nombre d'utilisateurs dans un rayon de ".$rayon." metres autour des coordonnees ".$lat.", ".$long;
		mysql_connect("db.youfid.fr", "youfid", "youfid")or die("cannot connect");
		mysql_select_db("youfid")or die("cannot select DB");
		$sqlGetCustomer = "SELECT * FROM mobileuser";
		$result = mysql_query($sqlGetCustomer);
		
		$count = 0;
		while($row = mysql_fetch_array($result))
		{
			if( (distance($lat, $long, doubleval($row['lattitude']), doubleval($row['longitude']))) * 1000 <= $rayon )$count++;
		}
		
		if($count == 0) $count+=1;
		
		if($count <= 3) $count *= 5;
		else if($count <= 6) $count *= 4;
		else if($count <= 10) $count *= 3;
		else if($count <= 20) $count *= 2;
		
		echo "<b>Resultat : " . $count . " utilisateurs a proximite </b> </br>";
		echo("<a href='./simuGeoLoc.php'> Retour </a> ");
	}
}

else 
{
	echo '
	<img src="http://www.youfid.fr/Content/logo.png"> </br> </br>
	<form>
	&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp
	<b>Adresse du marchand : </b> </br>
	</br>Rue : <input type="text" name="rue"> (ex : 1 rue de la Paix)
	</br>Code postal : <input type="text" name="postcode"> (ex : 75002)
	</br>Ville : <input type="text" name="ville"> (ex : Paris)
	</br>Pays : <input type="text" name="pays"> (ex : France)
	</br>Rayon du perimetre a couvrir : <input type="text" name="rayon"> metres
	</br> </br> &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp
	<input type="submit" name="button" id="button" value="Simuler !">
	</form>
	';
}

?>
