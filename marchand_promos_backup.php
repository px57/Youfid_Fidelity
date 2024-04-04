<html>
	<?php
		require_once("include/database.class.php");
                require_once("include/session.class.php");
                $session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist'];
		$_SESSION['selector_current_location'] = "marchand_promos.php";
		require_once("header.php");
		 
		if (!isset($_SESSION['nb_client']))
			$_SESSION['nb_client'] = array();
		require_once('dev/service/dbLogInfo.php');
		$marchand_id = $_SESSION['selector'];
		if ($_SESSION['previous'] != $_SESSION['selector'])
			$_SESSION['remember'] = array();
		
		$_SESSION['previous'] = $_SESSION['selector'];
		mysql_connect("$host", "$username", "$password")or die("cannot connect");
		mysql_select_db("$db_name")or die("cannot select DB");
		$getCadeau = "SELECT * FROM cadeau WHERE marchand_id ='"
					. mysql_real_escape_string($marchand_id)
					. "'";
		$resultCadal = mysql_query($getCadeau);
		$tbl_name1 = "marchand";
		$sqlGetMarchand = "SELECT * FROM $tbl_name1 WHERE `id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
		$result2 = mysql_query($sqlGetMarchand);
		$rowMarchand = mysql_fetch_array($result2);
		
		$sqlGetPromo = "SELECT * FROM message WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			 . "' && `type` = '"
			. mysql_real_escape_string("promo")
			. "'";
	$result = mysql_query($sqlGetPromo);
	$nb_use = 0;
	while ($rowPromo = mysql_fetch_array($result)) {
	  if($rowPromo['message'] != "A proximité"){	
		$start_date = date("Y-m-01");
		$endDate = date("Y-m-31");
		$getTime = "SELECT * FROM message_has_mobileuser WHERE `message_id` = '"
			. mysql_real_escape_string($rowPromo['id'])
			 . "' && `date_creation` <= '"
			. mysql_real_escape_string($endDate)
			. "' && `date_creation` >= '"
			. mysql_real_escape_string($start_date)
			. "'";
		$resultTime = mysql_query($getTime);
		if (mysql_num_rows($resultTime)) {
			$nb_use += 1;
		}
	  }
	}
	$used = $rowMarchand['max_promo'] - $nb_use;
	?>
	<script type="text/javascript" charset="utf-8">
 		 $(function() {
 		 $(".time").datepicker();
  		  });
	</script>
	
	<script>
	function pop(A, B) {
		window.alert('Promotion envoyée avec succès à ' + A + ' personnes. Il vous reste ' + B + ' promotions.');
	}
	</script>
	
	<script>
	function popError() {
		window.alert('Erreur : Vérifiez les données saisies.');
	}
	</script>
	
	<script language="javascript"> 

	function verif3(){ 
		
	if ((document.getElementById("fidelity").checked == true
			|| document.getElementById("last_authent").checked == true
			|| document.getElementById("min_points").checked == true
			|| document.getElementById("ancient").checked == true
			|| document.getElementById("next_present").checked == true)
			&& document.getElementById("ALL").checked == true){
		document.getElementById("ALL").checked = false;
			}
	
	else if (document.getElementById("fidelity").checked == false
			&& document.getElementById("last_authent").checked == false
			&& document.getElementById("min_points").checked == false
			&& document.getElementById("ancient").checked == false
			&& document.getElementById("next_present").checked == false
			&& document.getElementById("ALL").checked == false){
		document.getElementById("ALL").checked = true;
	}
	
	
	}
	
	
	function verif4(){ 
	
	 if (document.getElementById("ALL").checked == true)	 {
		document.getElementById("fidelity").checked = false;
		document.getElementById("last_authent").checked = false;
		document.getElementById("min_points").checked = false;
		document.getElementById("ancient").checked = false;
		document.getElementById("next_present").checked = false;

	}
	else if (document.getElementById("fidelity").checked == false
			&& document.getElementById("last_authent").checked == false
			&& document.getElementById("min_points").checked == false
			&& document.getElementById("ancient").checked == false
			&& document.getElementById("next_present").checked == false
			&& document.getElementById("ALL").checked == false){
		document.getElementById("ALL").checked = true;
	}
	
	}
	
	function verif5(){ 
		
	if ((document.getElementById("scanloc").checked == true
			|| document.getElementById("time").checked == true)
			&& document.getElementById("ALL").checked == true){
		document.getElementById("ALL").checked = false;
			}
	
	else if (document.getElementById("scanloc").checked == false
			&& document.getElementById("time").checked == false
			&& document.getElementById("ALL").checked == false){
		document.getElementById("ALL").checked = true;
	}
	}
	</script>
	
	<?php
	if ($_SESSION['sent'] == 2){
		$_SESSION['sent'] = 0;
		echo "<script>popError();</script>";
		
	} 
	
	if ($_SESSION['sent'] == 1){
		$_SESSION['sent'] = 0;
		$cb = count($_SESSION['nb_client']);
		echo "<script>pop($cb, $used);</script>";
		
	}
	
		$sqlMarchand = "SELECT * FROM marchand WHERE supermarchand_id = $marchand_id";
		$result = mysql_query($sqlMarchand);
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			if (isset($row['zip_code'])) {
				$LocArray[$i] = $row['zip_code'];
				$i++;
			}
		}
		$LocArray = array_unique($LocArray, SORT_REGULAR);
		foreach($LocArray as $key => $value) { 
  			if($value == "") { 
    			unset($LocArray[$key]); 
  			} 
		} 
		$LocArray2 = array_values($LocArray);
		sort($LocArray2);
	
		$arrayRem = $_SESSION['remember'];
	
	$titre = isset($arrayRem['titre']) ? $arrayRem['titre'] : '';
	$contenu = isset($arrayRem['contenu']) ? $arrayRem['contenu'] : '';
	$date_debut = isset($arrayRem['date_debut']) ? $arrayRem['date_debut'] : date('m/d/Y');
	$date_fin = isset($arrayRem['date_fin']) ? $arrayRem['date_fin'] : date('m/d/Y');
	$fidelity = isset($arrayRem['fidelity']) &&  $arrayRem['fidelity'] ? 'checked' : '';
	$fid = isset($arrayRem['fid']) ? $arrayRem['fid'] : '';
	$last_authent  = isset($arrayRem['last_authent']) &&  $arrayRem['last_authent'] ? 'checked' : '';
	$combien = isset($arrayRem['combien']) ? $arrayRem['combien'] : '';
	$chrono = isset($arrayRem['chrono']) ? $arrayRem['chrono'] : '';
	$min_points = isset($arrayRem['min_points']) &&  $arrayRem['min_points'] ? 'checked' : '';
	$points = isset($arrayRem['points']) ? $arrayRem['points'] : '';
	$ancient = isset($arrayRem['ancient']) &&  $arrayRem['ancient'] ? 'checked' : '';
	$nb_ancient = isset($arrayRem['nb_ancient']) ? $arrayRem['nb_ancient'] : '';
	$next_present = isset($arrayRem['next_present']) &&  $arrayRem['next_present'] ? 'checked' : '';
	$next_cadeau = isset($arrayRem['next_cadeau']) ? $arrayRem['next_cadeau'] : '';
	
	$scanloc = isset($arrayRem['scanloc']) &&  $arrayRem['scanloc'] ? 'checked' : '';
	$localisation = isset($arrayRem['localisation']) ? $arrayRem['localisation'] : '';
	$time  = isset($arrayRem['time']) &&  $arrayRem['time'] ? 'checked' : '';
	$combien = isset($arrayRem['combien']) ? $arrayRem['combien'] : '';
	$chrono = isset($arrayRem['chrono']) ? $arrayRem['chrono'] : '';
	?>
	
	
	<form action="lib/calc_promo2.php" method="post" >
		<div id="left">
			<br/> <br/>
			<?php echo $used; ?> Promos restantes ce mois-ci !
			<br/> <br/> <br/>
			Titre (max. 35 carac.) :
			<br/> <br/>
			<textarea class="promotext" cols="60" rows="1" name="titre" maxlength="35"><?php echo $titre; ?></textarea>
			<br/> <br/> <br/>
			Contenu (max. 220 carac.) :
			<br/> <br/>
			<textarea class="promotext" cols="60" rows="7" name="contenu" maxlength="220"><?php echo $contenu; ?></textarea>
			<br/> <br/>
			Du <input type="text" name="date_debut" class="time" value="<?php echo $date_debut; ?>" /> au <input type="text" name="date_fin" class="time" value="<?php echo $date_fin; ?>"/>

		</div>
	
		<div id="right">
			<br/> <br/> 
			Envoyé aux :
			<br/> <br/>
			<?php if ($rowMarchand['is_supermarchand'] != 1){ ?>
			<input type="checkbox" id="fidelity" name="fidelity" onclick="verif3()" <?php echo $fidelity; ?>> <input type="text" name="fid" value="<?php echo $fid; ?>"></input> clients les plus fidèles.
			<br/> <br/>
			<input type="checkbox" id="last_authent" name="last_authent" onclick="verif3()" <?php echo $last_authent; ?>> Clients dont la derniere authentification date de plus de  <select class="inputpromo" name="combien"><?php $i = 1; while ($i < 31) {if($i == $combien){echo "<option selected>$i";} else{echo "<option>$i";} $i++;} ?></select>
			<select class="inputpromo"  name="chrono"><option <?php  if ($chrono == 'Jours') {echo "selected";} ?>>Jours<option <?php  if ($chrono == 'Semaines') {echo "selected";} ?>>Semaines<option <?php  if ($chrono == 'Mois') {echo "selected";} ?>>Mois<option <?php  if ($chrono == 'Années') {echo "selected";} ?>>Années</select>
			<br/> <br/>
			<input type="checkbox" id="min_points" name="min_points" onclick="verif3()" <?php echo $min_points; ?>> Clients ayant au moins <input type="text" name="points" value="<?php echo $points; ?>"></input> points.
			<br/> <br/>
			<input type="checkbox" id="ancient" name="ancient" onclick="verif3()" <?php echo $ancient; ?>> <input type="text" name="nb_ancient" value="<?php echo $nb_ancient; ?>"></input>clients les plus anciens.
			<br/> <br/>
			<input type="checkbox" id="next_present" name="next_present" onclick="verif3()" <?php echo $next_present; ?>> Clients a une visite de <select name="next_cadeau"><?php while ($row = mysql_fetch_array($resultCadal)) {
				if ($row['nom'] == $next_cadeau){
					echo "<option selected> " . $row['nom'];
				} 
				else {
					echo "<option> " . $row['nom'];
				}	
			} 
				echo "<option> Tous les cadeaux";?></select>
			<br/> <br/>
			<?php }
			else {
				?>
				<input  type="checkbox" id="scanloc" name="scanloc" <?php echo $scanloc; ?> onclick="verif5()"> Clients scannés dans le <select name="localisation" class="inputpromo"><?php $i = 0; while (isset($LocArray2[$i])) {if ($LocArray2[$i] == $localisation) {echo "<option selected>$LocArray2[$i]";} else {echo "<option>$LocArray2[$i]";} $i++;} ?></select>
			<br/> <br/>
			<input  type="checkbox" id="time" name="time" value="time" <?php echo $time; ?> onclick="verif5()"> Dans le(s) dernier(e)(s) <select class="inputpromo" name="combien"><?php $i = 1; while ($i < 31) {if($i == $combien){echo "<option selected>$i";} else{echo "<option>$i";} $i++;} ?></select>
			<select class="inputpromo" name="chrono"><option>Jours<option>Semaines<option>Mois<option>Années</select>
			<br/> <br/>
				
				<?php }?>
			<input type="checkbox" id="ALL" name="ALL" value="ALL" onclick="verif4()"  <?php if ($fidelity != 'checked' && $last_authent != 'checked' && $min_points != 'checked' && $ancient != 'checked' && $next_present != 'checked'){echo 'checked';} ?>> Tous les clients
			<br/> <br/>
				<input type='submit' name='submit1' value='Calculer'> = <?php echo count($_SESSION['nb_client']); ?> <input type='submit' name='submit2' value='Envoyer!' <?php if ($used <= 0){ echo  'disabled';} ?>>
			<br />
			</form>
			<FORM METHOD='LINK' ACTION='histo_promo.php'><INPUT TYPE='submit' VALUE='Historique'></FORM>   <FORM METHOD='LINK' ACTION='marchand_email_promo.php'><INPUT TYPE='submit' VALUE='E-mailing' <?php if ($used <= 0 || $rowMarchand['is_email_actif'] == '0'){ echo  'disabled';} ?>></FORM>
		</div>
		<div class="clear">
	</form>
	
	
	
	<?php 
		require_once("footer.php"); 
	?>
