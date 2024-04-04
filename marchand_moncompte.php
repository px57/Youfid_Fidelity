<html>
	<?php
		require_once("include/database.class.php");
                require_once("include/session.class.php");
                $session = new Session();
 
		
		require_once('dev/service/dbLogInfo.php');
		$tbl_marchand="marchand";
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		$_SESSION['selector_current_location'] = "marchand_moncompte.php";
		require_once("header.php");
		
		//////////////////////////////////////////////////
		/// Recuperation des infos du marchand
		
		mysql_connect("$host", "$username", "$password")or die("cannot connect");
		mysql_select_db("$db_name")or die("cannot select DB");
			
		$sqlGetMerchant = "SELECT * FROM $tbl_marchand WHERE `id`='"
			.mysql_real_escape_string(($_SESSION['selector']))
			. "'";
			
		$result = mysql_query($sqlGetMerchant);
			
		$rowNb = mysql_num_rows($result);
		if ($rowNb)
		{
			$row = mysql_fetch_array($result);
			
			$name = $row['name'];
			$phone = $row['phone'];
			
			$address = $row['address'] . ", " . $row['zip_code'] . ", " . $row['city'];
						
			$website = $row['site_internet'];
			$fb_page = $row['page_fb'];
			
			$horaires = $row['horaire'];
			$pin = $row['is_pin_marchand'];
			$pin_code = $row['pin_code'];
			
			$bienvenue = $row['offre_bienvenue'];
		}
	?>
	
	<!-- Marchand_MONCOMPTE FORM -->
	<div id="new_marchant_content" style="margin:10px 30px">
		<h2> Mon Compte </h2>
		<div class="new_merchant_up">
			<div class="clear"></div>
			<div class="new_merchant_up_left left">
				<label>Nom du marchand : </label><br /><input type="text" title="Nom du marchand" name="name" value="<?php echo($name) ?>" disabled="disabled"></br>
				<label>Numero de telephone : </label><br /><input type="text" title="Numero de téléphone" name="phone" value="<?php echo($phone) ?>" disabled="disabled"><br/>
				<label>Page Facebook : </label><br /><input type="text" title="Page Facebook" name="fb_page" value="<?php echo($fb_page) ?>" disabled="disabled"><br/>
			</div>
			
			<div class="new_merchant_up_right left">
				<label>Adresse : </label><br /><input type="text" title="Page Facebook" name="fb_page" value="<?php echo($address) ?>" disabled="disabled"><br/>
				<label>Site internet : </label><br /><input type="text" title="Site internet" name="website" value="<?php echo($website) ?>" disabled="disabled"><br/>
			</div>	
			<div class="clear"></div>
		</div>
		
		<hr />
		
		<div >
			<label>Horaires : </label><br />
			<input type="text" title="Horaires d'ouverture" name="horaires" size="30" maxlength="255" style="width:75%" value='<?php echo($horaires); ?>' disabled="disabled"/>
		</div>
		
		<hr />
		
		<div class="new_merchant_up">
			<div class="clear"></div>
			
			<form id="merchant_update" name="merchant_update" action="post">
			
				<div class="new_merchant_up_left left">
					<label>Offre première authentification : </label><br /><input type="text" title="Offre premiere authentification" name="bienvenue" value="<?php echo($bienvenue) ?>" disabled="disabled"><br/>
					<label>Code Pin : </label><br /><input id="pin_code" type="text" title="Votre code PIN" name="pin_code" value="<?php echo($pin_code) ?>"><br/>
				</div>
				
				<div class="new_merchant_up_right left">
					<div class="customswitch left">
							<label class="switchlabel left">PIN Vérification marchand</label>
							<div class="onoffswitch left">
							    <input type="checkbox" name="pinmarchand" class="onoffswitch-checkbox" id="myonoffswitch" <?php if ($pin == "1")echo("checked") ?>>
							    <label class="onoffswitch-label" for="myonoffswitch">
							        <div class="onoffswitch-inner"></div>
							        <div class="onoffswitch-switch"></div>
							    </label>
							</div>
					</div>	
				</div>
			
				<div class="button_holder">
					<div class="new_merchant_up_left left">	
					</div>
					<div class="new_merchant_up_right left">
						<input type="button" onclick="onClickUpdateMarchand()" value="Actualiser"></button></br><span id="update_result"></span>
					</div>
				</div>
			</form>
			<div class="clear"></div>
			<a href="marchand_moncompte_changepassword.php">Changer votre mot de passe YouFid</a>
		</div>
		
	</div>
	
	<!-- Script Gestion formulaire -->
	<script type="text/javascript" src="static/js/marchand_mon_compte.js" charset="utf-8"></script>
	
	<?php
		require_once("footer.php"); 
	?>
</html>
