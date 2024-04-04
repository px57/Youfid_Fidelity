<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" type="image/ico" href="http://www.datatables.net/favicon.ico" />

		<title>YouFid - BackOffice</title>
		<style type="text/css" title="currentStyle">
			
			@import "./static/css/change_password.css";
		
			@import "./static/css/jquery-tooltip/tooltipsy.css";
			@import "./static/css/styles.css";
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
					
					<div id="change_password_content left" style="margin:10px 30px">
						
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
							
							<button id="send">Changer le mot de passe</button>
						</form>
						
						<br/>
						<a href="index.php">Se connecter au BackOffice Youfid</a>
						
					</div>
					<script type="text/javascript" src="static/js/change_password.js" charset="utf-8"></script>
<?php 

require_once("footer.php"); ?>