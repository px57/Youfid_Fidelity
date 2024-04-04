<html>
	
	<?php
		require_once("include/database.class.php");
        	require_once("include/session.class.php");
        	$session = new Session();
 
		
		require_once("lib/Logger.class.php");
		require_once("lib/db_functions.php");
				
		$logger = new Logger('./logs/');
	
		$logger->log('debug', 'youfid_master_moncompte', "in file", Logger::GRAN_MONTH);
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		if (isset($_POST['shoplist']))
		{
			$_SESSION['selector'] = $_POST['shoplist'];
			unset($_POST['shoplist']);
		} 
		
		$_SESSION['selector_current_location'] = "youfid_master_moncompte.php";
		require_once("header.php"); 
	?>
	
	<!-- GESTION DU SELECTOR COMMERCIAUX -->
	<?php
		
		//echo($_SESSION['selector']);
		
		$button_value = "Créer un nouveau marchand";
		
		/// If Selector is on "NEW": On prérempli les champs
		//if ($_SESSION['selector'] == "NEW")
		if ($_SESSION['selector'] == 0)
		{
			if (isset($_SESSION['current_label_selection']) && !empty($_SESSION['current_label_selection']))
			{
				echo($_SESSION['current_label_selection']);
				
				$tbl_marchand="marchand";
				require_once('dev/service/dbLogInfo.php');
				
				mysql_connect("$host", "$username", "$password")or die("cannot connect");
				mysql_select_db("$db_name")or die("cannot select DB");
				
				$sqlGetMerchant = "SELECT * FROM $tbl_marchand WHERE `name`='"
					.mysql_real_escape_string($_SESSION['current_label_selection'])
					. "' AND `is_supermarchand`='1'";
				
				$result = mysql_query($sqlGetMerchant);
				
				$rowNb = mysql_num_rows($result);
				if ($rowNb)
				{
					$row = mysql_fetch_array($result);
					
					$name = $row['name'];
					$phone = $row['phone'];
					$address = $row['address'];
					$city = $row['city'];
					$zip_code = $row['zip_code'];
					
					$website = $row['site_internet'];
					$fb_page = $row['page_fb'];
					$contact = $row['contact'];
					$horaires = $row['horaire'];
					$logo = $row['logo'];
					$logo_mini = $row['logo_mini'];
					
					$acceuil = $row['is_accueil_client'];
					$emailing = $row['is_email_actif'];
					$pin = $row['is_pin_marchand'];
					$signalezvous = $row['is_signalez_vous'];
					$is_active = $row['is_active'];
					$email_bo = $row['email_backoffice'];
					$label = $row['label_id'];
					$pin_code = $row['pin_code'];
					//$supermarchand = $row['is_supermarchand'];
					
					$max_scan = $row['max_scan'];
					$max_promo = $row['max_promo'];
				}
			}
			else
			{
				$name = "Nom du marchand";
				$phone = "Téléphone du marchand";
				$address = "Addresse du marchand";
				$city = "Ville du marchand";
				$zip_code = "Code postal du marchand";
				
				$website = "Site web du marchand";
				$fb_page = "Page Facebook du marchand";
				$contact = "Contact:";
				$horaires = "Horaires";
				$logo = "Adresse du logo";
				$email_bo = "Email backoffice";
				$label = -1;
				
				// Et tous les switch...
				$acceuil = "1";
				$emailing = "1";
				$pin = "1";
				$signalezvous = "1";
				$is_active = "0";
				$pin_code = "";
				
				$max_scan = 0;
				$max_promo = 0;
			}
			$supermarchand = "0";
		}
		/// Sinon: On recupere les datas du marchands selectioné
		else
		{
			$tbl_marchand="marchand";
			require_once('dev/service/dbLogInfo.php');
			
			$button_value = "Editer";
			
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
				$address = $row['address'];
				$city = $row['city'];
				$zip_code = $row['zip_code'];
				
				$website = $row['site_internet'];
				$fb_page = $row['page_fb'];
				$contact = $row['contact'];
				$horaires = $row['horaire'];
				$logo = $row['logo'];
				$logo_mini = $row['logo_mini'];
				
				$acceuil = $row['is_accueil_client'];
				$emailing = $row['is_email_actif'];
				$pin = $row['is_pin_marchand'];
				$signalezvous = $row['is_signalez_vous'];
				$supermarchand = $row['is_supermarchand'];
				$is_active = $row['is_active'];
				$email_bo = $row['email_backoffice'];
				$label = $row['label_id'];
				$pin_code = $row['pin_code'];
				
				$max_scan = $row['max_scan'];
				$max_promo = $row['max_promo'];
			}
			
		}

		/// Definit si le user a les droits necessaire pour changer de logo
		$change_logo = can_update_logo($label);
	?>
	
	<!-- Definition de logo_path en variable JS -->
	<script type="text/javascript">
		/// Path du logo en variable global
		var logo_path = "<?php echo($logo) ?>";
		
		if (logo_path == "Adresse du logo")
			logo_path = "";
	</script>
	<!-------------------------------------------->
	<script type="text/javascript" src="static/js/other_mon_compte.js"></script>
	
	
	<!-- NEW MERCHANT FORM -->
	<div id="new_marchant_content" style="margin:10px 30px">
		
		<h2>Mon Compte</h2>
		<form id="new_merchant_form" method="post" action="lib/commerciaux_register_marchand.php" enctype="multipart/form-data">
			
			<?php
				if(isset($_SESSION['user_error']) && !empty($_SESSION['user_error']))
				{
					echo '
							<tr>
								<td class="error center" style="font-size:13px">' . $_SESSION['user_error'] . '</td>
							</tr>
					';
					$_SESSION['user_error'] = "";
				}
			?>
			
			<div class="new_merchant_up">
			
				<div class="clear"></div>
				<div class="new_merchant_up_left left">
					
					<label>Label du marchand : </label></br>
					<div>
						<select name="categorylist" id="label_selector" onchange="do_label_selector_change(this)">
							<?php 
								require_once('lib/spinner_list_label.php');
								if (isset($_SESSION['current_label_selection']))
									unset($_SESSION['current_label_selection']); 
							?>
						</select>
						
						<script type="text/javascript">
							
							function do_label_selector_change(current)
							{
								//alert("hello");
								var selection = current.options[current.selectedIndex].value;
								//alert(selection);
								var xhr = new XMLHttpRequest();
								
								xhr.onreadystatechange = function() {
									if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
										if ((xhr.responseText) == "OK") 
											//document.location.reload(true);
											location.replace("youfid_master_moncompte.php");
									}
								};
								xhr.open("GET", "lib/label_selector_change.php?selection=" + selection, true);
								xhr.send(null);
							}
							
						</script>
						
						<!-- BOUTON PLUS ET POPUP -->
						<a class="modalbox" href="#inline" title="Ajouter une nouvelle catégorie"><img class="icons" width="21" height="21" src="static/images/plus_button.png"/></a><br>
					</div>
					<?php  $name = htmlspecialchars($name, ENT_QUOTES); ?>
					<label>Nom du marchand : </label><br /><input type="text" title="Nom du marchand" name="name" value='<?php echo($name); ?>' onfocus="if (this.value == '<?php echo($name); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($name);  ?>';}" /><br/>
					
					<label>Numero de telephone : </label><br /><input type="text" title="Numero de téléphone" name="phone" id="phone" value='<?php echo($phone); ?>' onfocus="if (this.value == '<?php echo($phone); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($phone); ?>';}" /><br/>
					<?php  $address = htmlspecialchars($address, ENT_QUOTES); ?>
					<label>Voierie : </label><br /><input type="text" title="Voierie" name="address" value='<?php echo($address); ?>' onfocus="if (this.value == '<?php echo($address); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($address); ?>';}" /><br/>
					<?php  $city = htmlspecialchars($city, ENT_QUOTES); ?>
					<label>Ville : </label><br /><input type="text" title="Ville" name="city" value='<?php echo($city); ?>' onfocus="if (this.value == '<?php echo($city); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($city); ?>';}" /><br/>
					<label>Code Postal : </label><br /><input type="text" title="Code postal" name="zip_code" value='<?php echo($zip_code); ?>' onfocus="if (this.value == '<?php echo($zip_code); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($zip_code); ?>';}" /><br/>
					<label>Code PIN : </label><br /><input placeholder="Code PIN" type="text" title="Code PIN du marchand" id="pin_code" name="pin_code" value='<?php echo($pin_code); ?>'/><br/>
				</div>
				
				<div class="new_merchant_up_right left">
					<label>Email backoffice : </label><br /><input type="text" title="Email de gestion pour le backoffice" name="email_bo" id="email_bo" value='<?php echo($email_bo); ?>' onfocus="if (this.value == '<?php echo($email_bo); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($email_bo); ?>';}" /><br />
					<label>Site internet : </label><br /><input type="text" title="Site internet" name="website" value='<?php echo($website); ?>' onfocus="if (this.value == '<?php echo($website); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($website); ?>';}" /><br />
					
					<label>Logo URL : </label><br /><input <?php if(!$change_logo)echo('disabled="disabled"'); ?> type="text" onKeyUp="display_url_picture(this)" title="Url du logo" name="logourl" value='<?php echo($logo); ?>' onfocus="if (this.value == '<?php echo($logo); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($logo); ?>';}" /><br />
					<label>Ou Logo PATH : </label><br /><input type="file" title="Path du logo" name="logopath" onchange="display_path_picture(this)" <?php if(!$change_logo)echo('disabled="disabled"'); ?>/><br/>

					<label>Page Facebook : </label><br /><input type="text" title="Page Facebook" name="fb_page" value='<?php echo($fb_page); ?>' onfocus="if (this.value == '<?php echo($fb_page); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($fb_page); ?>';}" /><br />
					<label>Contact : </label><br /><textarea style="resize:none" rows="5" cols="50" title="Contact" name="contact" onfocus="if (this.value =='<?php echo($contact); ?>'){this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($contact); ?>';}"><?php echo($contact); ?></textarea><br />
				</div>
				<div class="clear"></div>
			</div>
			
			
			
			<div class="new_merchant_bottom">
				<div class="clear"></div>
				
				<!-- Preview du logo-->
				<div id ="divdisplay2">
					<img src="">
				</div>
					
				<hr />
					
				<div>
					<label>Horaires : </label><br />
					<input type="text" title="Horaires d'ouverture" name="horaires" size="30" maxlength="255" style="width:75%" value='<?php echo($horaires); ?>' onfocus="if (this.value == '<?php echo($horaires); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo($horaires); ?>';}" />
				</div>
				
				<div class="new_merchant_bottom_left left">
					
					<div class="customswitch left">
						<label class="switchlabel left">Accueil</label>
						<div class="clientmarchandswitch left">
						    <input type="checkbox" name="clientmarchandswitch" class="clientmarchandswitch-checkbox" id="myclientmarchandswitch" <?php if ($acceuil == "0")echo("checked") ?>>
						    <label class="clientmarchandswitch-label" for="myclientmarchandswitch">
						        <div class="clientmarchandswitch-inner"></div>
						        <div class="clientmarchandswitch-switch"></div>
						    </label>
						</div>
					</div>
					
					<div class="customswitch left">
						<label class="switchlabel left">E-mailing</label>
						<div class="onoffswitch left">
						    <input type="checkbox" name="emailing" class="onoffswitch-checkbox" id="myonoffswitch3" <?php if ($emailing == "1")echo("checked") ?>>
						    <label class="onoffswitch-label" for="myonoffswitch3">
						        <div class="onoffswitch-inner"></div>
						        <div class="onoffswitch-switch"></div>
						    </label>
						</div>
					</div>
				</div>
				
				<div class="new_merchant_bottom_right left">
					
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
					
					<div class="customswitch left">
						<label class="switchlabel left">Signalez vous</label>
						<div class="onoffswitch left">
						    <input type="checkbox" name="signalezmarchand" class="onoffswitch-checkbox" id="myonoffswitch2" <?php if ($signalezvous == "1")echo("checked") ?>>
						    <label class="onoffswitch-label" for="myonoffswitch2">
						        <div class="onoffswitch-inner"></div>
						        <div class="onoffswitch-switch"></div>
						    </label>
						</div>
					</div>
					
					<div class="customswitch left">
						<label class="switchlabel left">Activation</label>
						<div class="onoffswitch left">
						    <input type="checkbox" name="is_active" class="onoffswitch-checkbox" id="myonoffswitch4" <?php if ($is_active == "1")echo("checked") ?>>
						    <label class="onoffswitch-label" for="myonoffswitch4">
						        <div class="onoffswitch-inner"></div>
						        <div class="onoffswitch-switch"></div>
						    </label>
						</div>
					</div>
					
				</div>
				<div class="clear"></div>
			</div>
			<hr />
			<div class="new_merchant_numbers_region">
				
				<label for="promocount">Limite promos par mois = </label><input name="promolimit" id="promocount" type="number" min="0" value="<?php echo($max_promo)?>"/>
				<label for="scancount">Max scan par jour et par client= </label><input name="maxscan" id="scancount" type="number" min="0" value="<?php echo($max_scan)?>"/>
				
			</div>
			
			<label for="is_supermarchand">Super Marchand</label>
			<input <?php if (isset($_SESSION['selector']) && $_SESSION['selector'] == "NEW")echo('style="margin-right: 36%"'); ?> type="checkbox" id="is_supermarchand" name="is_supermarchand" <?php if ($supermarchand == "1")echo("checked") ?>/>
			<?php if (isset($_SESSION['selector']) && $_SESSION['selector'] != "NEW")echo('<br/>');?>
			<!--<input style="height: 23.6px;" type="submit" name="submit" value="<?php echo($button_value); ?>">-->
			<input style="height: 23.6px;" type="button" onclick="onClickFormMonCompte_master()"  value="<?php echo($button_value); ?>">
			
			
			</br><span style="color: red" id="check_form_result"></span></br>
		</form>
		
		<?php
				if (isset($_SESSION['selector']) && $_SESSION['selector'] != "NEW")
					echo("<a href=\"lib/remove_merchant.php\" onclick=\"return(confirm('Etes vous sûre de vouloir supprimer ce marchant ?'));\"><button style=\"height: 23.6px;\">Supprimer ce marchand</button> </a>");
		?>
		
	</div>
	
	<!-- LABEL POPUP -->
	<!-- hidden inline form -->
	<div id="inline">
		<h2>Ajouter une nouvelle catégorie</h2>
					
		<form id="contact" name="contact" action="#" method="post">
			<label for="label_name">Entrez le nom de la nouvelle catégorie</label>
			<input id="label_name" name="label_name" class="txt">
							
			<button id="send">Créer la nouvelle catégorie</button>
		</form>
	</div>
	
	<!-- Script for Picture Preview -->
	<script type="text/javascript" src="static/js/logo_preview.js"></script>
	<!-------------------------------->
	
	<!-- basic fancybox setup -->
	<script type="text/javascript">
		
		$(document).ready(function() {
			
			$(".modalbox").fancybox();
			$("#contact").submit(function() { return false; });
			
			$("#send").on("click", function(){
				var nameval = $("#label_name").val();
				var namelen = nameval.length;
				
				if(namelen < 3) {
					$("#label_name").addClass("error");
				}
				else if(namelen >= 3){
					$("#label_name").removeClass("error");
				}
					
				if(namelen >= 3) {
					// first we hide the submit btn so the user doesnt click twice
					$("#send").replaceWith("<em>sending...</em>");
					
					$.ajax({
						type: 'POST',
						url: 'register_new_label.php',
						data: $("#contact").serialize(),
						success: function(data) 
						{
							if(data == "true") 
							{
								$("#contact").fadeOut("fast", function(){
									$(this).before("<p><strong>Success! The label has been created successfully.</strong></p>");
									setTimeout("$.fancybox.close()", 1000);
									setTimeout("document.location.reload(true)", 1000);
								});
							}
							else if(data == "false") 
							{
								$("#contact").fadeOut("fast", function(){
									$(this).before("<p><strong>Error! The label can not be created for the moment. Please check error message at the top of the page.</strong></p>");
									//setTimeout("$.fancybox.close()", 1000);
									//setTimeout("document.location.reload(true)", 1000);
								});
							}
						}
					});
				}
			});
		});
	</script>
		
	<!------------------------------->
	
	<?php 
		//require_once('labelform.php');
		require_once("footer.php"); 
	?>
	
</html>
