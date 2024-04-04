<html>
	<?php
		require_once("include/database.class.php");
	        require_once("include/session.class.php");
        	$session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		;
		
		$_SESSION['selector_current_location'] = "youfid_master_pushgeo.php";
		if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
		require_once("header.php");
		$marchand_id = $_SESSION['selector'];
		$tbl_name1 = "marchand";
		$sqlGetMarchand = "SELECT * FROM $tbl_name1 WHERE `id` = '"
				. mysql_real_escape_string($marchand_id)
				. "'";
		$result2 = mysql_query($sqlGetMarchand);
		$rowMarchand = mysql_fetch_array($result2);
	?>
	
	<?php if ($marchand_id != 0 ){?>

	<div style="margin:10px 30px">
		<form method="post" action="lib/update_pushinfo.php">
		<div class="clear"></div>
		<div class="new_merchant_up_left left">
			<br />
			<p>Lorsqu’un utilisateur s’étant déjà authentifié chez un commerçant passe à proximité de ce dernier, il reçoit un push contenant la dernière promo qui lui a été envoyée par ce marchand.<br/></p>
			<br />
			<p>Le push géolocalisé est un message d’accroche destiné aux utilisateurs n’ayant jamais reçu aucune promo de la part du marchand.</p><br />
			
		</div>
			
		<div class="new_merchant_up_right left">
			<br/>
			<p>Envoyer les notifications push géolocalisé si l’utilisateur est dans un rayon de :</p><br /><br/> 
			<input type="text"  style="max-width: 100px" name="distance" value="<?php echo $rowMarchand['distance_push']; ?>">
			<p style="display: inline">Mètres autour du commerce</p>
			<br/> <br/><br/> <br/>
			<input type="checkbox" name="is_push_active" id="is_push_active" <?php if ($rowMarchand['is_push_actif'] == '1'){echo 'checked';}?>/> <p style="display: inline">Activer le push géolocalisé</p>
			<br/> <br/> <br/> <br/><br/> <br/>
			
		</div>
		<div class="clear"></div>
		<input type="submit" value="Valider" style="width: 100px">
		</form>
	</div>
		<?php 
 
 }
else {
	echo "Veuillez choisir un marchand dans la liste déroulante.";
	
}
 
 
 require_once 'footer.php'; ?>
</html>
