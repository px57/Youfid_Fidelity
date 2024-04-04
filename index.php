<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" type="image/ico" href="http://www.datatables.net/favicon.ico" />
		<link rel="stylesheet" type="text/css" media="all" href="static/fancybox/jquery.fancybox.css">

		<title>YouFid - BackOffice</title>
		<style type="text/css" title="currentStyle">


			@import "./static/css/jquery-tooltip/tooltipsy.css";
			@import "./static/css/styles.css";

			@import "./static/css/popup.css";

			@import "./static/css/easyslider.screen.css";
			@import "./static/css/flexigrid/flexigrid.css";
			@import "./static/css/jquery-ui/custom/jquery-ui-1.8.24.custom.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.accordion.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.all.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.autocomplete.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.base.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.button.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.core.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.datepicker.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.dialog.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.progressbar.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.resizable.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.selectable.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.slider.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.tabs.css";
			@import "./static/css/jquery-ui/custom/jquery.ui.theme.css";
		</style>



		<script type="text/javascript" src="./static/js/jquery/jquery-1.8.2.min.js"></script>
		<script type="text/javascript" src="./static/js/jquery/jquery-ui-1.8.24.custom.min.js"></script>
		<script type="text/javascript" language="javascript" src="./js/jquery-ui-timepicker-addon-0.6.2.js"></script>
		<script type="text/javascript" src="./static/js/jquery/jquery.cookie.js"></script>
		<script type="text/javascript" language="javascript" src="./js/disapear2.js"></script>
		<!--<script type="text/javascript" language="javascript" src="./js/jquery-1.7.2.min.js"></script>-->
		<script type="text/javascript" language="javascript" src="./js/jquery.dataTables.js"></script>
		<script type="text/javascript" src="./static/js/jquery-tooltip/tooltipsy.min.js"></script>
		<script type="text/javascript" src="./static/js/easySlider1.7.js"></script>
		<!--script type="text/javascript" src="./static/js/flexigrid/flexigrid.js"></script-->

		<!-- Script for label Popup : At the end for page loading issues -->
		<script type="text/javascript" src="scripts/jquery.min.js"></script>
		<!---------------------------->
		<!-- Script for label Popup -->
	  	<script type="text/javascript" src="static/fancybox/jquery.fancybox.js?v=2.0.6"></script>
		<!-------------------------------->


	</head>



	<body>
		<div id="general" class="align-center ui-corner-all shadow">
            <div id="header" class="ui-corner-top">
                <div id="title">
					YouFid - BackOffice
				</div>
            </div>
            <div id="main">
				<div class="clear"></div>
				<div id="content">
					<div style="padding:0px 0px 10px; width:30%; margin:10% auto" class="ui-widget ui-widget-content ui-corner-all widget-shadow" id="logon-form">
						<div style="padding:5px" class="ui-helper-reset ui-helper-clearfix ui-widget-header">
							Log on
						</div>
						<div style="margin:10px 30px">
							<form  method="post" action="check.php">
								<table border="0" cellspacing="5" cellpadding="5">
									<?php
										if(isset($login_error) && !empty($login_error))
										{
											echo '
												<tr>
													<td class="error center" style="font-size:13px">' . $login_error . '</td>
												</tr>
											';
										}
									?>
									<tr>
										<td><input type="text" name="f_key" placeholder="key ..."></td>
									</tr>
									<tr>
										<td><input type="text" size="10" name="f_login" value="<?php if(isset($_COOKIE['cookiemail'])) { echo $_COOKIE['cookiemail']; } else {echo 'Login';} ?>" onfocus="if (this.value == 'Login') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Login';}"></td>
									</tr>
									<tr>
										<td><input type="password" size="10" name="f_pass" value="<?php if(isset($_COOKIE['cookiepass'])) { echo $_COOKIE['cookiepass']; } else {echo 'Password';} ?>" onfocus="if (this.value == 'Password') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Password';}"></td>
									</tr>
									<tr>
										<td>
											<label>Se souvenir de moi</label>
	    									<input name="remember" type="checkbox" <?php if(isset($_COOKIE['cookiemail']) && ($_COOKIE['cookiemail']!="")) {echo "checked";}  ?> />
	    								</td>
									</tr>

									<tr>
										<td colspan="2" align="center">
											<input type="submit" name="submit" value="Envoyer">
										</td>
									</tr>
								</table>
							 </form>
							 <!-- BOUTON PLUS ET POPUP -->
						</div>
						<div style="height:7px" class="clear"></div>

					</div>

					<p><a class="modalbox" href="#inline">Mot de passe oublié?</a></p>
						<!-- LABEL POPUP -->
						<!-- hidden inline form -->
						<div id="inline">
							<h2>Récuperer votre mot de passe</h2>

							<form id="contact" name="contact" action="#" method="post">
								<label for="login">Votre login back office Youfid:</label>
								<input type="text" id="login" name="login" class="txt">

								<button id="send">Send E-mail</button>
							</form>
					</div>


					<script type="text/javascript" charset="utf-8">

						$(document).ready(function()
						{
							$(".modalbox").fancybox();
							$("#contact").submit(function() { return false; });


							$("#send").on("click", function(){
								var loginval  = $("#login").val();
								var loginlen    = loginval.length;

								if(loginlen < 4) {
									$("#login").addClass("error");
								}
								else if(loginlen >= 4){
									$("#login").removeClass("error");
								}

								if(loginlen >= 4) {
									$("#send").replaceWith("<em>sending...</em>");

									$.ajax({
										type: 'POST',
										url: 'lib/recover_password.php',
										data: $("#contact").serialize(),
										success: function(data) {
											if(data == "true") {
												$("#contact").fadeOut("slow", function(){
													$(this).before("<p><strong>Votre mot de passe vient d'etre envoyé a votre adresse email.</strong></p>");
													setTimeout("$.fancybox.close()", 2000);
												});
											}

											if(data == "false") {
												$("#contact").fadeOut("slow", function(){
													$(this).before("<p><strong>Aucun utilisateur trouvé. Avez vous spécifié le bon nom d'utilisateur?</strong></p>");
													setTimeout("$.fancybox.close()", 2000);
												});
											}
										}
									});
								}
							});

							oTable = $('#example').dataTable({
							  		"bJQueryUI": true,
									"sPaginationType": "full_numbers"
							});
						});
				</script>



<?php
require_once("footer.php"); ?>
