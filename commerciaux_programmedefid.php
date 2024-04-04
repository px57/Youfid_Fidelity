<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
	require_once "dev/service/utils.php";

	$login_url = $url_loyalty . 'services/user/login';
	$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
	$result =  postRequest($login_url, $json_login);
	$loginResult = json_decode($result, true);

	$session = new Session();
 
	$_SESSION['selector_current_location'] = "commerciaux_programmedefid.php";
	require_once 'header.php';
	if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
	$marchand_id = $_SESSION['selector'];
	$tbl_name="cadeau";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$sqlGetCadeau = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
	$result = mysql_query($sqlGetCadeau);
	
	
	$tbl_name1 = "marchand";
	$sqlGetMarchand = "SELECT * FROM $tbl_name1 WHERE `id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
	$result2 = mysql_query($sqlGetMarchand);
	$rowMarchand = mysql_fetch_array($result2);
	echo $marchand_id;
?>
  
  <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="example" width="100%">
			  <thead>
			  <TR>
			 <TH> Cout du produit </TH>
			 <TH> Produit </TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
			  while($row = mysql_fetch_array($result)){
					
						echo "<TR>";
						echo "<TD>" . $row['cout']. "</TH>";
						echo "<TD>" . $row['nom']. "</TD>";						
						echo "</TR>";
			  }
			}
			?>
			</tbody>
			</TABLE>
			 <form id="new_merchant_form" method="post" action="lib/update_programmedefid.php">

			Offre de bienvenue :
			
			<input style="width : 200px" type="text" name="bienvenue" value='<?php echo($rowMarchand['offre_bienvenue']); ?>' onfocus="if (this.value == '<?php echo($rowMarchand['offre_bienvenue']); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($rowMarchand['offre_bienvenue']); ?>';}" />  
			<br/>
			<?php
			if ($rowMarchand['is_accueil_client'] == "1"){
				echo "Regle de conversion : " 	
			?>
			
			<input type="text" name="points" value='<?php echo($rowMarchand['points_for_accueil']); ?>' onfocus="if (this.value == '<?php echo($rowMarchand['points_for_accueil']); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($rowMarchand['points_for_accueil']); ?>';}" />  
			points par scan.
			<br/>
			
			<?php
			}
			else {
				
				$jsonApp = '{
				"wsAccessPublicKey" 	:  "8293582c-1e0c-40ff-9d59-10cb18834855",
				"wsAccessToken" 		: "' . $loginResult['wsAccess']['wsAccessToken'] . '",
				"applicationPublicId" 	: "' . $rowMarchand['application_id'] . '"
				}';
				
				$appUrl = $url_loyalty . "services/application/get";
				$appResult = postRequest($appUrl, $jsonApp);
				$appJson = json_decode($appResult, true);
				echo "Regle de conversion : " . $appJson['application']['inRulePoint'] .  " points pour " . $appJson['application']['inRuleCash'] . " €<br />";
			}
				echo "Texte explicatif : ";
				
			
			?>
			<input type="text" name="txt_exp" value='<?php echo($rowMarchand['texte_explicatif']); ?>' onfocus="if (this.value == '<?php echo($rowMarchand['texte_explicatif']); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($rowMarchand['texte_explicatif']); ?>';}" />  
			<input type="submit" name="submit" value="Valider">

		
								 
			
			
			</form>	
 
 
 
 
 <?php require_once 'footer.php'; ?>
