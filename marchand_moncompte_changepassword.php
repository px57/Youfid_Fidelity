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
		
		$_SESSION['selector_current_location'] = "marchand_moncompte_changepassword.php";
		require_once("header.php");
	?>
	
	<div id="change_password_content left" style="margin:10px 30px">
		<div class="clear"></div>	
		<h2>Changer votre mot de passe YouFid:</h2>
		<form id="change_password_form" name="change_password_form" action="#" method="post">
			<label for="login">Login Youfid:</label></br>
			<input id="login" name="login" type="text"/></br>
								
			<label for="old_password">Mot de passe actuel:</label></br>
			<input id="old_password" name="old_password" type="password"/></br>
								
			<label for="new_password">Nouveau mot de passe:</label></br>
			<input id="new_password" name="new_password" type="password"/></br>
								
			<label for="new_password_bis">Nouveau mot de passe (confirmation):</label></br>
			<input id="new_password_bis" name="new_password_bis" type="password"/></br>
								
			<!--<label id="error_msg" >TOTO</label></br>-->
			<span id="error_msg"></span><br />
								
			<button id="send_change_password">Changer le mot de passe</button>
		</form>
							
		<br/>
		<a href="marchand_moncompte.php">Retour</a>
		<div class="clear"></div>				
	</div>
	<script type="text/javascript" src="static/js/change_password.js" charset="utf-8"></script>
	
	<?php 
		require_once("footer.php"); 
	?>
</html>
