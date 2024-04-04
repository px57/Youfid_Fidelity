<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
	require_once "dev/service/utils.php";

	$login_url = $url_loyalty . 'services/user/login';
	$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
	$result =  postRequest($login_url, $json_login);
	$loginResult = json_decode($result, true);
	
        $session = new Session();
 
	$_SESSION['selector_current_location'] = "youfid_master_programmedefid.php";
	if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
	require_once 'header.php';
	
	$marchand_id = $_SESSION['selector'];
	$tbl_name="cadeau";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$sqlGetCadeau = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'ORDER BY  `cadeau`.`cout` ASC ";
	$result = mysql_query($sqlGetCadeau);
	
	
	$tbl_name1 = "marchand";
	$sqlGetMarchand = "SELECT * FROM $tbl_name1 WHERE `id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
	$result2 = mysql_query($sqlGetMarchand);
	$rowMarchand = mysql_fetch_array($result2);
?>

<script language="javascript"> 

function menu(ID){ 
if (document.getElementById('cancellink' + ID).style.display=='none'){ 
	document.getElementById('cancellink' + ID).style.display='';
	document.getElementById('modiflink' + ID).style.display='none';
	document.getElementById('nom' + ID).style.display='none';
	document.getElementById('cout' + ID).style.display='none';
	document.getElementById('formnom' + ID).style.display='';
	document.getElementById('formcout' + ID).style.display='';
	document.getElementById('accept' + ID).style.display='';


} else { 
	document.getElementById('cancellink' + ID).style.display='none';
	document.getElementById('modiflink' + ID).style.display='';
	document.getElementById('nom' + ID).style.display='';
	document.getElementById('cout' + ID).style.display='';
	document.getElementById('formnom' + ID).style.display='none';
	document.getElementById('formcout' + ID).style.display='none';
		document.getElementById('accept' + ID).style.display='none';


} 

} 

</script> 
 <script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			 $('#table').dataTable({ 
			 
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"bSort": false
			});
			} );
	</script>

<?php if ($marchand_id != 0 ){?>
  <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="table" width="100%">
			  <thead>
			  <TR>
			 <TH> Cout du produit </TH>
			 <TH> Produit </TH>
			 <TH> Supprimer </TH>
			 <TH> Modifier </TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
				while($row = mysql_fetch_array($result)){
						echo "<TR>";
						echo "<form id='updatekdo" . $row['id'] . "' method='post' action='lib/update_row.php?idcadeau=" .  $row['id'] . "'>";
						echo "<TD><span id='cout" . $row['id'] . "'>" . intval($row['cout']) . "</span>		<input style='display:none;' id='formcout" . $row['id'] . "' type='text' name='cout' value='" . $row['cout'] . "'></TD>";
						echo "<TD><span id='nom" . $row['id'] . "'>" . $row['nom']. "</span>		<input style='display:none;' id='formnom" . $row['id'] . "' type='text' name='nom' value='" . $row['nom'] . "'></TD>";
						$url ="/lib/update_tab.php?idcadeau=" .  $row['id'];						
						//echo "<TD>" . "form action='$url'><input type='submit' value='Supprimer'></form>"  . "</TD>";
						echo  "<TD> <a href='" . $url . "'>Supprimer</a> </TD>";
						echo "<TD> <a href='#'id='modiflink" . $row['id'] . "'  onclick='menu(" . $row['id'] . ")'>Modifier</a>	<input style='display:none;' id='accept" . $row['id'] . "' type='submit' name='submit' value='Valider'> 	<a href='#'id='cancellink" . $row['id'] . "' onclick='menu(" . $row['id'] . ")' style='display:none;'>Annuler</a> 				</TD>";
						echo "</form>";
						echo "</TR>";
			  }
			}
			?>
			<TR>
				<form id='createkdo' method='post' action='lib/create_row.php'>
					<TD><input  id='formcoutcreate' type='text' name='cout'></TD>
					<TD><input  id='formnamecreate' type='text' name='nom'></TD>
					<TD> - </TD>
					<TD> <input type='submit' name='submitcrea' value='Créer'> </TD>
					 <input type='hidden' name='marchand_id' value="<?php echo $marchand_id ?>">
				</form>
			</TR>	
			</tbody>
			</TABLE>
			</form>
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
				echo "Regle de conversion : ";
				echo "<input  style='max-width: 100px' type='text' name='inRulePoint' value='" . $appJson['application']['inRulePoint'] . "'/>";
				echo " points pour ";
				echo "<input  style='max-width: 100px' type='text' name='inRuleCash' value='" .$appJson['application']['inRuleCash'] . "'/>";
				echo " €<br />";
			}
				echo "Texte explicatif : ";
				
			
			?>
			<input type="text" name="txt_exp" value='<?php echo($rowMarchand['texte_explicatif']); ?>' onfocus="if (this.value == '<?php echo($rowMarchand['texte_explicatif']); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($rowMarchand['texte_explicatif']); ?>';}" />  
			<input type="submit" name="submit" value="Valider">

		
								 
			
			
			</form>	
 
 
 
 
 <?php 
 
 }
else {
	echo "Veuillez choisir un marchand dans la liste déroulante.";
	
}
 
 
 require_once 'footer.php'; ?>
