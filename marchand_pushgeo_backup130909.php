<html>
	<?php
		require_once("include/database.class.php");
                require_once("include/session.class.php");
                $session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		$_SESSION['selector_current_location'] = "marchand_pushgeo.php";
		require_once("header.php");
		
		require_once('dev/service/dbLogInfo.php');
				
		mysql_connect("$host", "$username", "$password")or die("cannot connect");
		mysql_select_db("$db_name")or die("cannot select DB");
		
		$tbl_marchand="marchand";
		/// Id Marchand
		$merchant_id = $_SESSION['selector'];
		
		$query = "SELECT * FROM $tbl_marchand WHERE `id`='"
			. mysql_real_escape_string($merchant_id)
			. "'";
			
		$result = mysql_query($query);
		
		$is_active = "0";
		if ($row = mysql_fetch_array($result))
			$is_active = $row['is_push_actif'];
	?>
	
	<div id="merchant_content" style="margin:10px 30px">
		<form id="marchand_push">
			<div class="clear"></div>
			<div class="new_merchant_up_left left">
				<div class="msg_region">
					<p>Lorsqu’un utilisateur s’étant déjà authentifié chez un commerçant passe à proximité de ce dernier, il reçoit un push contenant la dernière promo qui lui a été envoyée par ce marchand.<br/></p>
					<p>Le message à compléter ici est un push d’accroche destiné aux utilisateurs n’ayant jamais reçu aucune promo de votre part.</p><br />
				</div>
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_lundi" id="is_lundi" checked="checked"/><label>Lundi de</label>
					</div>
					<select id="l_start" name="l_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="l_end" name="l_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_mardi" id="is_mardi" checked="checked"/><label>Mardi de</label>
					</div>
					<select id="ma_start" name="ma_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="ma_end" name="ma_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_mercredi" id="is_mercredi" checked="checked"/><label>Mercredi de</label>
					</div>
					<select id="me_start" name="me_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="me_end" name="me_end" ><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_jeudi" id="is_jeudi" checked="checked"/><label>Jeudi de</label>
					</div>
					<select id="j_start" name="j_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="j_end" name="j_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_vendredi" id="is_vendredi" checked="checked"/><label>Vendredi de</label>
					</div>
					<select id="v_start" name="v_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="v_end" name="v_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_samedi" id="is_samedi" checked="checked"/><label>Samedi de</label>
					</div>
					<select id="s_start" name="s_start"><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="s_end" name="s_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<div class="day_elem">
					<div class="day_label">
						<input type="checkbox" name="is_dimanche" id="is_dimanche" checked="checked"/><label>Dimanche de</label>
					</div>
					<select id="d_start" name="d_start" ><?php require("lib/spinner_list_hours.php") ?></select>
					<label> à </label><select id="d_end" name="d_end"><?php require("lib/spinner_list_hours.php") ?></select></br>
				</div>
				
				<input type="checkbox" id="is_active" name="is_active" <?php if ($is_active == "1")echo('checked="checked"') ?> /><label>Activer le push géolocalisé</label></br>
			</div>
				
			<div class="new_merchant_up_right left">
				<div class="text_region">
					<label>Titre (max.40 car.):</label>
					<input type="text" title="L'intitulé de votre PUSH (non modifiable)" value="A proximité" name="push_title" id="push_title" READONLY/><br />
					
					<hr />
					
					<label>Contenu (max.150 car.):</label>
					<textarea name="push_msg" title="Le message de votre PUSH" id="push_msg" rows="10" placeholder="Message"></textarea><br />
					
					<hr />
					
					<input type="button" onclick="onClickMarchandPush()" value="Valider"><br />
				</div>
			</div>
		</form>
		<div class="clear"></div>
		
	</div>
	<span id="form_result"></span></br>
	<script type="text/javascript" src="static/js/marchant_push.js"></script>
	
	<?php
		require_once("footer.php"); 
	?>
</html>
